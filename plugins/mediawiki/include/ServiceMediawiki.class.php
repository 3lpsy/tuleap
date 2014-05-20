<?php

class ServiceMediawiki extends Service {

    public function renderInPage(HTTPRequest $request, $title, $template, $presenter = null) {
        $this->displayHeader($request, $title);

        if ($presenter) {
            $this->getRenderer()->renderToPage($template, $presenter);
        }

        $this->displayFooter();
        exit;
    }

    private function getRenderer() {
        return TemplateRendererFactory::build()->getRenderer(dirname(MEDIAWIKI_BASE_DIR).'/templates');
    }

    public function displayHeader(HTTPRequest $request, $title) {
        $toolbar = array();
        if ($this->userIsAdmin($request)) {
            $toolbar[] = array(
                'title' => $GLOBALS['Language']->getText('global', 'Administration'),
                'url'   => MEDIAWIKI_BASE_URL .'/forge_admin?'. http_build_query(array(
                    'group_id'   => $request->get('group_id'),
                ))
            );
        }

        $title       = $title.' - '.$GLOBALS['Language']->getText('plugin_mediawiki', 'service_lbl_key');
        $breadcrumbs = array();
        parent::displayHeader($title, $breadcrumbs, $toolbar);
    }

    /**
     * @param HTTPRequest $request
     * @return bool
     */
    public function userIsAdmin(HTTPRequest $request) {
        $user = $request->getCurrentUser();
        $forge_user_manager = new User_ForgeUserGroupPermissionsManager(
            new User_ForgeUserGroupPermissionsDao()
        );
        $has_special_permission = $forge_user_manager->doesUserHavePermission(
            $user,
            new User_ForgeUserGroupPermission_MediawikiAdminAllProjects()
        );

        return $has_special_permission || $user->isMember($request->getProject()->getID(), 'A');
    }
}