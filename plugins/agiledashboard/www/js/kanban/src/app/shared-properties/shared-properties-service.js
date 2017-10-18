export default SharedPropertiesService;

function SharedPropertiesService() {
    var property = {
        detailed_view_key    : 'detailed-view',
        compact_view_key     : 'compact-view',
        user_id              : undefined,
        kanban               : undefined,
        view_mode            : undefined,
        user_is_admin        : false,
        project_id           : undefined,
        nodejs_server        : undefined,
        nodejs_server_version: undefined,
        uuid                 : undefined,
        dashboard_dropdown   : undefined,
        widget_id            : 0,
        kanban_url           : ''
    };

    return {
        getUserId                   : getUserId,
        setUserId                   : setUserId,
        doesUserPrefersCompactCards : doesUserPrefersCompactCards,
        setUserPrefersCompactCards  : setUserPrefersCompactCards,
        getViewMode                 : getViewMode,
        setViewMode                 : setViewMode,
        getKanban                   : getKanban,
        setKanban                   : setKanban,
        getUserIsAdmin              : getUserIsAdmin,
        setUserIsAdmin              : setUserIsAdmin,
        setProjectId                : setProjectId,
        getProjectId                : getProjectId,
        getNodeServerAddress        : getNodeServerAddress,
        setNodeServerAddress        : setNodeServerAddress,
        getUUID                     : getUUID,
        setUUID                     : setUUID,
        setNodeServerVersion        : setNodeServerVersion,
        getNodeServerVersion        : getNodeServerVersion,
        setDashboardDropdown        : setDashboardDropdown,
        getDashboardDropdown        : getDashboardDropdown,
        getUserIsOnWidget           : getUserIsOnWidget,
        setWidgetId                 : setWidgetId,
        getWidgetId                 : getWidgetId,
        getKanbanUrl                : getKanbanUrl,
        setKanbanUrl                : setKanbanUrl
    };

    function getUserId() {
        return property.user_id;
    }

    function setUserId(user_id) {
        property.user_id = user_id;
    }

    function doesUserPrefersCompactCards() {
        return property.view_mode !== property.detailed_view_key;
    }

    function setUserPrefersCompactCards(is_collapsed) {
        return property.view_mode = is_collapsed ? property.compact_view_key : property.detailed_view_key;
    }

    function setViewMode(view_mode) {
        property.view_mode = view_mode;
    }

    function getViewMode() {
        return property.view_mode;
    }

    function getKanban() {
        return property.kanban;
    }

    function setKanban(kanban) {
        property.kanban = kanban;
    }

    function getUserIsAdmin() {
        return property.user_is_admin;
    }

    function setUserIsAdmin(user_is_admin) {
        property.user_is_admin = user_is_admin;
    }

    function setProjectId(project_id) {
        property.project_id = project_id;
    }

    function getProjectId() {
        return property.project_id;
    }

    function getNodeServerAddress() {
        return property.nodejs_server;
    }

    function setNodeServerAddress(nodejs_server) {
        property.nodejs_server = nodejs_server;
    }

    function setUUID(uuid){
        property.uuid = uuid;
    }

    function getUUID() {
        return property.uuid;
    }

    function setNodeServerVersion(nodejs_server_version) {
        property.nodejs_server_version = nodejs_server_version;
    }

    function getNodeServerVersion() {
        return property.nodejs_server_version;
    }

    function setDashboardDropdown(dashboard_dropdown) {
        property.dashboard_dropdown = dashboard_dropdown;
    }

    function getDashboardDropdown() {
        return property.dashboard_dropdown;
    }

    function getUserIsOnWidget() {
        return property.widget_id !== 0;
    }

    function setWidgetId(widget_id) {
        property.widget_id = widget_id;
    }

    function getWidgetId() {
        return property.widget_id;
    }

    function setKanbanUrl(kanban_url) {
        property.kanban_url = kanban_url;
    }

    function getKanbanUrl() {
        return property.kanban_url;
    }
}
