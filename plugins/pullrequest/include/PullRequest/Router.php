<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\PullRequest;

use Tuleap\PullRequest\Exception\UnknownBranchNameException;
use Tuleap\PullRequest\Exception\PullRequestCannotBeCreatedException;
use Tuleap\PullRequest\Exception\PullRequestAlreadyExistsException;
use Codendi_Request;
use Feedback;
use GitRepositoryFactory;
use GitRepository;
use UserManager;
use CSRFSynchronizerToken;

class Router {

    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var GitRepositoryFactory
     */
    private $git_repository_factory;

    /**
     * @var PullRequestCreator
     */
    private $pull_request_creator;

    public function __construct(
        PullRequestCreator $pull_request_creator,
        GitRepositoryFactory $git_repository_factory,
        UserManager $user_manager
    ) {
        $this->pull_request_creator   = $pull_request_creator;
        $this->git_repository_factory = $git_repository_factory;
        $this->user_manager           = $user_manager;
    }

    public function route(Codendi_Request $request) {
        $repository_id = $request->get('repository_id');
        $project_id    = $request->get('group_id');

        $repository = $this->git_repository_factory->getRepositoryById($repository_id);

        if ($repository->getProjectId() != $project_id) {
            $this->redirectInRepositoryViewBecauseOfBadRequest($repository_id, $project_id);
        }

        switch ($request->get('action')) {
            case 'generatePullRequest':
                $this->generatePullRequest($request, $repository, $project_id);
                break;
            default:
                $this->redirectInRepositoryViewBecauseOfBadRequest($repository_id, $project_id);
                break;
        }
    }

    private function generatePullRequest(Codendi_Request $request, GitRepository $repository, $project_id) {
        $branch_src    = $request->get('branch_src');
        $branch_dest   = $request->get('branch_dest');
        $repository_id = $repository->getId();
        $user          = $this->user_manager->getCurrentUser();

        $token = new CSRFSynchronizerToken('/plugins/git/?action=view&repo_id=' . $repository->getId() . '&group_id=' . $project_id);
        $token->check();

        if (! $repository->userCanRead($user)) {
            $this->redirectInRepositoryViewWithErrorMessage(
                $repository_id,
                $project_id,
                $GLOBALS['Language']->getText('plugin_pullrequest', 'user_cannot_read_repository')
            );
        }

        try {
            $generated_pull_request = $this->pull_request_creator->generatePullRequest(
                $repository,
                $branch_src,
                $branch_dest
            );
        } catch (UnknownBranchNameException $exception) {
            $this->redirectInRepositoryViewWithErrorMessage(
                $repository_id,
                $project_id,
                $GLOBALS['Language']->getText('plugin_pullrequest', 'generate_pull_request_branch_error')
            );

        } catch (PullRequestCannotBeCreatedException $exception) {
            $this->redirectInRepositoryViewWithErrorMessage(
                $repository_id,
                $project_id,
                $GLOBALS['Language']->getText('plugin_pullrequest', 'pull_request_cannot_be_created')
            );

        } catch (PullRequestAlreadyExistsException $exception) {
            $this->redirectInRepositoryViewWithErrorMessage(
                $repository_id,
                $project_id,
                $GLOBALS['Language']->getText('plugin_pullrequest', 'pull_request_already_exists')
            );
        }

        if (! $generated_pull_request) {
            $this->redirectInRepositoryViewWithErrorMessage(
                $repository_id,
                $project_id,
                $GLOBALS['Language']->getText('plugin_pullrequest', 'generate_pull_request_error')
            );
        }

        return $this->redirectToPullRequestViewIntoGitRepository($generated_pull_request, $project_id);
    }

    private function redirectInRepositoryViewWithErrorMessage($repository_id, $project_id, $message) {
        $GLOBALS['Response']->addFeedback(Feedback::ERROR, $message);

        $this->redirectInRepositoryView($repository_id, $project_id);
    }

    private function redirectInRepositoryViewBecauseOfBadRequest($repository_id, $project_id) {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            $GLOBALS['Language']->getText('plugin_pullrequest', 'invalid_request')
        );

        $this->redirectInRepositoryView($repository_id, $project_id);
    }

    private function redirectInRepositoryView($repository_id, $project_id) {
        $GLOBALS['Response']->redirect(
            "/plugins/git/?action=view&repo_id=$repository_id&group_id=$project_id"
        );
    }

    private function redirectToPullRequestViewIntoGitRepository(PullRequest $generated_pull_request, $project_id) {
        $repository_id            = $generated_pull_request->getRepositoryId();
        $generated_pull_request_id= $generated_pull_request->getId();

        $GLOBALS['Response']->redirect(
            "/plugins/git/?action=pull-requests&repo_id=$repository_id&group_id=$project_id#/pull-requests/$generated_pull_request_id"
        );
    }
}
