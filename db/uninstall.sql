DROP TABLE IF EXISTS plugin_pullrequest_review;
DROP TABLE IF EXISTS plugin_pullrequest_comments;
DROP TABLE IF EXISTS plugin_pullrequest_inline_comments;
DROP TABLE IF EXISTS plugin_pullrequest_timeline_event;
DROP TABLE IF EXISTS plugin_pullrequest_label;

DELETE FROM reference_group WHERE reference_id = 31 OR reference_id = 32;
DELETE FROM reference WHERE id = 31 OR id = 32;
