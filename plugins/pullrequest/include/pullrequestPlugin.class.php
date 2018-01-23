<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'autoload.php';
require_once 'constants.php';

use Tuleap\Glyph\GlyphFinder;
use Tuleap\Glyph\GlyphLocation;
use Tuleap\Glyph\GlyphLocationsCollector;
use Tuleap\Label\CanProjectUseLabels;
use Tuleap\Label\CollectionOfLabelableDao;
use Tuleap\Git\GitRepositoryDeletionEvent;
use Tuleap\Label\LabeledItemCollection;
use Tuleap\Layout\IncludeAssets;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Label\LabeledItemCollector;
use Tuleap\PullRequest\Label\PullRequestLabelDao;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use Tuleap\PullRequest\Router;
use Tuleap\PullRequest\PullRequestCreator;
use Tuleap\PullRequest\REST\ResourcesInjector;
use Tuleap\PullRequest\PluginInfo;
use Tuleap\PullRequest\GitExec;
use Tuleap\PullRequest\AdditionalInfoPresenter;
use Tuleap\PullRequest\AdditionalActionsPresenter;
use Tuleap\PullRequest\AdditionalHelpTextPresenter;
use Tuleap\PullRequest\PullRequestPresenter;
use Tuleap\PullRequest\Factory;
use Tuleap\PullRequest\Dao;
use Tuleap\PullRequest\PullRequestUpdater;
use Tuleap\PullRequest\PullRequestMerger;
use Tuleap\PullRequest\PullRequestCloser;
use Tuleap\PullRequest\FileUnidiffBuilder;
use Tuleap\PullRequest\InlineComment\InlineCommentUpdater;
use Tuleap\PullRequest\InlineComment\Dao as InlineCommentDao;
use Tuleap\PullRequest\Timeline\Dao as TimelineDao;
use Tuleap\PullRequest\Timeline\TimelineEventCreator;
use Tuleap\PullRequest\Reference\ReferenceFactory;
use Tuleap\PullRequest\Reference\ProjectReferenceRetriever;
use Tuleap\PullRequest\Reference\ReferenceDao;
use Tuleap\PullRequest\Tooltip\Presenter;

class pullrequestPlugin extends Plugin
{

    const PR_REFERENCE_KEYWORD          = 'pr';
    const PULLREQUEST_REFERENCE_KEYWORD = 'pullrequest';
    const REFERENCE_NATURE              = 'pullrequest';

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);

        $this->addHook(Event::SERVICE_CLASSNAMES);
        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(Event::GET_REFERENCE);
        $this->addHook(Event::GET_PLUGINS_AVAILABLE_KEYWORDS_REFERENCES);
        $this->addHook(Event::GET_AVAILABLE_REFERENCE_NATURE);
        $this->addHook('ajax_reference_tooltip');
        $this->addHook(CollectionOfLabelableDao::NAME);
        $this->addHook(LabeledItemCollection::NAME);
        $this->addHook(GlyphLocationsCollector::NAME);
        $this->addHook(CanProjectUseLabels::NAME);

        if (defined('GIT_BASE_URL')) {
            $this->addHook('cssfile');
            $this->addHook('javascript_file');
            $this->addHook(REST_GIT_PULL_REQUEST_ENDPOINTS);
            $this->addHook(REST_GIT_PULL_REQUEST_GET_FOR_REPOSITORY);
            $this->addHook(GIT_ADDITIONAL_INFO);
            $this->addHook(GIT_ADDITIONAL_ACTIONS);
            $this->addHook(GIT_ADDITIONAL_BODY_CLASSES);
            $this->addHook(GIT_ADDITIONAL_PERMITTED_ACTIONS);
            $this->addHook(GIT_HANDLE_ADDITIONAL_ACTION);
            $this->addHook(GIT_ADDITIONAL_HELP_TEXT);
            $this->addHook(GIT_VIEW);
            $this->addHook(GIT_HOOK_POSTRECEIVE_REF_UPDATE, 'gitHookPostReceive');
            $this->addHook(REST_GIT_BUILD_STATUS, 'gitRestBuildStatus');
            $this->addHook(GitRepositoryDeletionEvent::NAME);
        }
    }

    public function getServiceShortname()
    {
        return 'plugin_pullrequest';
    }

    /**
     * @see Plugin::getDependencies()
     */
    public function getDependencies()
    {
        return array('git');
    }

    public function service_classnames($params)
    {
        $params['classnames'][$this->getServiceShortname()] = 'PullRequest\\Service';
    }

    public function cssfile($params)
    {
        if (strpos($_SERVER['REQUEST_URI'], GIT_BASE_URL . '/') === 0) {
            echo '<link rel="stylesheet" type="text/css" href="' . $this->getPluginPath() . '/js/angular/bin/assets/tuleap-pullrequest.css" />';
            echo '<link rel="stylesheet" type="text/css" href="' . $this->getThemePath() . '/css/style.css" />';
        }
    }

    public function javascript_file()
    {
        if (strpos($_SERVER['REQUEST_URI'], GIT_BASE_URL . '/') === 0) {
            $include_asset = new IncludeAssets(ForgeConfig::get('codendi_dir').'/src/www/assets', '/assets');
            echo '<script type="text/javascript" src="'. $include_asset->getFileURL('labels-box.js') .'"></script>';
            echo '<script type="text/javascript" src="'.$this->getPluginPath().'/js/angular/bin/assets/tuleap-pullrequest.js"></script>'."\n";
            echo '<script type="text/javascript" src="'.$this->getPluginPath().'/js/move-button-back.js"></script>';
        }
    }

    public function process(Codendi_Request $request)
    {
        $user_manager           = UserManager::instance();
        $event_manager          = EventManager::instance();
        $git_repository_factory = $this->getRepositoryFactory();

        $pull_request_merger = new PullRequestMerger(
            $git_repository_factory
        );
        $pull_request_creator = new PullRequestCreator(
            $this->getPullRequestFactory(),
            new Dao(),
            $pull_request_merger,
            $event_manager
        );

        $router = new Router($pull_request_creator, $git_repository_factory, $user_manager);
        $router->route($request);
    }

    /**
     * @return Tuleap\PullRequest\PluginInfo
     */
    public function getPluginInfo()
    {
        if (!$this->pluginInfo) {
            $this->pluginInfo = new PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    /**
     * @see REST_RESOURCES
     */
    public function rest_resources(array $params)
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }

    /**
     * @see REST_GIT_PULL_REQUEST_ENDPOINTS
     */
    public function rest_git_pull_request_endpoints($params)
    {
        $params['available'] = true;
    }

    /**
     * @see REST_GIT_PULL_REQUEST_GET_FOR_REPOSITORY
     */
    public function rest_git_pull_request_get_for_repository($params)
    {
        $version = $params['version'];
        $class   = "\\Tuleap\\PullRequest\\REST\\$version\\RepositoryResource";
        $repository_resource = new $class;

        $params['result'] = $repository_resource->getPaginatedPullRequests(
            $params['repository'],
            $params['query'],
            $params['limit'],
            $params['offset']
        );
    }

    /**
     * @see GIT_ADDITIONAL_INFO
     */
    public function git_additional_info($params)
    {
        $repository = $params['repository'];

        if (! $repository->isMigratedToGerrit()) {
            $nb_pull_requests = $this->getPullRequestFactory()->getPullRequestCount($repository);

            $renderer  = $this->getTemplateRenderer();
            $presenter = new AdditionalInfoPresenter($repository, $nb_pull_requests);

            $params['info'] = $renderer->renderToString($presenter->getTemplateName(), $presenter);
        }
    }

    /**
     * @see GIT_ADDITIONAL_ACTIONS
     */
    public function git_additional_actions($params)
    {
        $repository = $params['repository'];
        $user       = $params['user'];

        if (! $repository
            || $repository->isMigratedToGerrit()
            || $user->isAnonymous()
        ) {
            return;
        }

        $git_exec = new GitExec($repository->getFullPath(), $repository->getFullPath());
        $renderer = $this->getTemplateRenderer();
        $csrf     = new CSRFSynchronizerToken('/plugins/git/?action=view&repo_id=' . $repository->getId() . '&group_id=' . $repository->getProjectId());

        $branches = $git_exec->getAllBranchNames();

        $dest_branches   = array();
        foreach ($branches as $branch) {
            $dest_branches[] = array('repo_id' => $repository->getId(), 'repo_name' => null, 'branch_name' => $branch);
        }

        $parent_repo = $repository->getParent();
        if ($parent_repo) {
            $git_exec        = new GitExec($parent_repo->getFullPath(), $parent_repo->getFullPath());
            $parent_branches = $git_exec->getAllBranchNames();
            foreach ($parent_branches as $branch) {
                $dest_branches[] = array('repo_id' => $parent_repo->getId(), 'repo_name' => $parent_repo->getFullName(), 'branch_name' => $branch);
            }
        }

        $presenter = new AdditionalActionsPresenter($repository, $csrf, $branches, $dest_branches);
        $params['actions'] = $renderer->renderToString($presenter->getTemplateName(), $presenter);
    }

    /**
     * @see GIT_ADDITIONAL_BODY_CLASSES
     */
    public function git_additional_body_classes($params)
    {
        if ($params['request']->get('action') === 'pull-requests') {
            $params['classes'][] = 'git-pull-requests';
        }
    }

    /**
     * @see GIT_ADDITIONAL_PERMITTED_ACTIONS
     */
    public function git_additional_permitted_actions($params)
    {
        $repository = $params['repository'];
        $user       = $params['user'];

        if ($repository && $repository->userCanRead($user) && ! $repository->isMigratedToGerrit()) {
            $params['permitted_actions'][] = 'pull-requests';
        }
    }

    /**
     * @see GIT_HANDLE_ADDITIONAL_ACTION
     */
    public function git_handle_additional_action($params)
    {
        $git_controller = $params['git_controller'];
        $repository     = $params['repository'];

        if ($params['action'] === 'pull-requests') {
            $params['handled'] = true;

            if ($repository) {
                $git_controller->addAction('getRepositoryDetails', array($repository->getProjectId(), $repository->getId()));
                $git_controller->addView('view');
            } else {
                $git_controller->redirectNoRepositoryError();
            }
        }
    }

    /**
     * @see GIT_ADDITIONAL_HELP_TEXT
     */
    public function git_additional_help_text($params)
    {
        $repository = $params['repository'];

        if (! $repository->isMigratedToGerrit()) {
            $renderer  = $this->getTemplateRenderer();
            $presenter = new AdditionalHelpTextPresenter();

            $params['html'] = $renderer->renderToString($presenter->getTemplateName(), $presenter);
        }
    }

    /**
     * @see GIT_VIEW
     */
    public function git_view($params)
    {
        $repository = $params['repository'];
        $user       = $params['user'];
        $request    = $params['request'];

        if ($request->get('action') === 'pull-requests') {
            $nb_pull_requests = $this->getPullRequestFactory()->getPullRequestCount($repository);
            $renderer         = $this->getTemplateRenderer();
            $presenter        = new PullRequestPresenter($repository->getId(), $user->getId(), $user->getShortLocale(), $nb_pull_requests);

            $params['view'] = $renderer->renderToString($presenter->getTemplateName(), $presenter);
        }
    }

    public function gitHookPostReceive($params) {
        $refname     = $params['refname'];
        $branch_name = $this->getBranchNameFromRef($refname);

        if ($branch_name != null) {
            $new_rev    = $params['newrev'];
            $repository = $params['repository'];
            $user       = $params['user'];

            $git_exec = new GitExec($repository->getFullPath(), $repository->getFullPath());
            if ($new_rev == '0000000000000000000000000000000000000000') {
                $this->abandonFromSourceBranch($user, $repository, $branch_name);
            } else {
                $pull_request_updater = new PullRequestUpdater(
                    $this->getPullRequestFactory(),
                    new PullRequestMerger($this->getRepositoryFactory()),
                    new InlineCommentDao(),
                    new InlineCommentUpdater(),
                    new FileUnidiffBuilder(),
                    $this->getTimelineEventCreator(),
                    $this->getRepositoryFactory()
                );
                $pull_request_updater->updatePullRequests($user, $git_exec, $repository, $branch_name, $new_rev);
            }

            if (! $user->isAnonymous()) {
                $this->markManuallyMerged($user, $repository, $branch_name, $new_rev);
            }
        }
    }

    private function markManuallyMerged(
        PFUser $user,
        GitRepository $dest_repository,
        $dest_branch_name,
        $new_rev
    ) {
        $pull_request_factory   = $this->getPullRequestFactory();
        $git_repository_factory = $this->getRepositoryFactory();
        $timeline_event_creator = $this->getTimelineEventCreator();

        $prs = $pull_request_factory->getOpenedByDestinationBranch($dest_repository, $dest_branch_name);

        foreach ($prs as $pr) {
            $repository = $git_repository_factory->getRepositoryById($pr->getRepoDestId());
            $git_exec = new GitExec($repository->getFullPath(), $repository->getFullPath());
            if ($git_exec->isAncestor($new_rev, $pr->getSha1Src())) {
                $pull_request_factory->markAsMerged($pr);
                $timeline_event_creator->storeMergeEvent($pr, $user);
            }
        }
    }

    private function abandonFromSourceBranch(PFUser $user, GitRepository $repository, $branch_name)
    {
        $pull_request_factory   = $this->getPullRequestFactory();
        $timeline_event_creator = $this->getTimelineEventCreator();
        $closer                 = new PullRequestCloser($this->getPullRequestFactory(), new PullRequestMerger($this->getRepositoryFactory()));

        $prs = $pull_request_factory->getOpenedBySourceBranch($repository, $branch_name);
        foreach ($prs as $pr) {
            $closer->abandon($pr);
            $timeline_event_creator->storeAbandonEvent($pr, $user);
        }
    }

    public function gitRestBuildStatus($params)
    {
        $factory = $this->getPullRequestFactory();
        $pull_requests = $factory->getOpenedBySourceBranch($params['repository'], $params['branch']);
        foreach($pull_requests as $pull_request) {
            $factory->updateLastBuildStatus($pull_request, $params['status'], time());
        }
    }

    private function getBranchNameFromRef($refname)
    {
        $prefix = 'refs/heads/';

        if (substr($refname, 0, strlen($prefix)) == $prefix) {
            $refname = substr($refname, strlen($prefix));
            return $refname;
        }

        return null;
    }

    private function getPullRequestFactory()
    {
        return new Factory(new Dao(), ReferenceManager::instance());
    }

    private function getRepositoryFactory()
    {
        return new GitRepositoryFactory(new GitDao(), ProjectManager::instance());
    }

    private function getTemplateRenderer()
    {
        return TemplateRendererFactory::build()->getRenderer(PULLREQUEST_BASE_DIR . '/templates');
    }

    private function getTimelineEventCreator()
    {
        return new TimelineEventCreator(new TimelineDao());
    }

    public function get_reference($params)
    {
        $keyword         = $params['keyword'];
        $pull_request_id = $params['value'];

        if ($this->isReferenceAPullRequestReference($keyword)) {
            $params['reference'] = $this->getReferenceFactory()->getReferenceByPullRequestId(
                $keyword,
                $pull_request_id
            );
        }
    }

    /**
     * @return ReferenceFactory
     */
    private function getReferenceFactory()
    {
        return new ReferenceFactory(
            $this->getPullRequestFactory(),
            $this->getRepositoryFactory(),
            new ProjectReferenceRetriever(new ReferenceDao()),
            new HTMLURLBuilder(
                $this->getRepositoryFactory()
            )
        );
    }

    private function isReferenceAPullRequestReference($keyword) {
        return $keyword === self::PR_REFERENCE_KEYWORD || $keyword === self::PULLREQUEST_REFERENCE_KEYWORD;
    }

    public function get_plugins_available_keywords_references($params)
    {
        $params['keywords'] = array_merge(
            $params['keywords'],
            array(self::PR_REFERENCE_KEYWORD, self::PULLREQUEST_REFERENCE_KEYWORD)
        );
    }

    public function get_available_reference_natures($params) {
        $nature = array(self::REFERENCE_NATURE => array(
            'keyword' => 'pullrequest',
            'label'   => 'Git Pull Request'
        ));

        $params['natures'] = array_merge($params['natures'], $nature);
    }

    public function ajax_reference_tooltip($params)
    {
        $reference = $params['reference'];
        if ($reference->getNature() === self::REFERENCE_NATURE) {
            $pull_request_id            = $params['val'];
            $pull_request               = $this->getPullRequestFactory()->getPullRequestById($pull_request_id);
            $pull_request_title         = $pull_request->getTitle();
            $pull_request_status        = $pull_request->getStatus();
            $pull_request_creation_date = $pull_request->getCreationDate();

            $renderer  = $this->getTemplateRenderer();
            $presenter = new Presenter(
                $pull_request_title,
                $pull_request_status,
                $pull_request_creation_date
            );
            echo $renderer->renderToString($presenter->getTemplateName(), $presenter);
        }
    }

    public function collectionOfLabelableDao(CollectionOfLabelableDao $event)
    {
        $event->add(new PullRequestLabelDao());
    }

    public function gitRepositoryDeletion(GitRepositoryDeletionEvent $event)
    {
        $dao = new Dao();
        $dao->deleteAllPullRequestsOfRepository($event->getRepository()->getId());
    }

    public function collectGlyphLocations(GlyphLocationsCollector $glyph_locations_collector)
    {
        $glyph_locations_collector->addLocation(
            'tuleap-pullrequest',
            new GlyphLocation(PULLREQUEST_BASE_DIR . '/glyphs')
        );
    }

    public function collectLabeledItems(LabeledItemCollection $event)
    {
        $git_plugin = PluginManager::instance()->getPluginByName('git');

        $labeled_item_collector = new LabeledItemCollector(
            new PullRequestLabelDao(),
            $this->getPullRequestFactory(),
            new PullRequestPermissionChecker(
                $this->getRepositoryFactory(),
                new URLVerification()
            ),
            new HTMLURLBuilder(
                $this->getRepositoryFactory()
            ),
            new GlyphFinder(
                EventManager::instance()
            ),
            new GitRepositoryFactory(new GitDao(), ProjectManager::instance()),
            UserManager::instance(),
            new UserHelper(),
            new Git_GitRepositoryUrlManager($git_plugin),
            $this->getTemplateRenderer()
        );

        $labeled_item_collector->collect($event);
    }

    public function canProjectUseLabels(CanProjectUseLabels $event)
    {
        if ($event->getProject()->usesService(GitPlugin::SERVICE_SHORTNAME)) {
            $event->projectCanUseLabels();
        }
    }
}
