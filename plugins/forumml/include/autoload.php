<?php
// @codingStandardsIgnoreFile
// @codeCoverageIgnoreStart
// this is an autogenerated file - do not edit
function autoload76a15acf20756881f0b4cce6409362e6($class) {
    static $classes = null;
    if ($classes === null) {
        $classes = array(
            'forumml_attachment' => '/ForumML_Attachment.class.php',
            'forumml_attachmentdao' => '/ForumML_AttachmentDao.class.php',
            'forumml_filestorage' => '/ForumML_FileStorage.class.php',
            'forumml_htmlpurifier' => '/ForumML_HTMLPurifier.class.php',
            'forumml_messagedao' => '/ForumML_MessageDao.class.php',
            'forumml_messagemanager' => '/ForumML_MessageManager.class.php',
            'forumml_mimedecode' => '/ForumML_mimeDecode.class.php',
            'forummlinsert' => '/ForumMLInsert.class.php',
            'forummlplugin' => '/forummlPlugin.class.php',
            'forummlplugindescriptor' => '/ForumMLPluginDescriptor.class.php',
            'forummlplugininfo' => '/ForumMLPluginInfo.class.php'
        );
    }
    $cn = strtolower($class);
    if (isset($classes[$cn])) {
        require dirname(__FILE__) . $classes[$cn];
    }
}
spl_autoload_register('autoload76a15acf20756881f0b4cce6409362e6');
// @codeCoverageIgnoreEnd
