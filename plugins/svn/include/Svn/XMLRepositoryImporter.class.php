<?php
/**
 * Copyright (c) Enalean SAS, 2016. All Rights Reserved.
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

namespace Tuleap\Svn;

use Logger;
use Project;
use SimpleXMLElement;
use Event;
use EventManager;
use SystemEventManager;
use System_Command_CommandException;
use SVN_AccessFile_Writer;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Svn\Repository\Repository;
use Tuleap\Svn\Repository\RepositoryManager;
use Tuleap\Svn\Repository\RuleName;
use Tuleap\Svn\AccessControl\AccessFileHistoryCreator;
use Tuleap\Svn\Admin\MailNotification;
use Tuleap\Svn\Admin\MailNotificationManager;
use ForgeConfig;

class XMLRepositoryImporter
{
    const SERVICE_NAME = 'svn';

    /** @var string */
    private $dump_file_path;

    /** @var string */
    private $name;

    /** @var string */
    private $access_file_contents;

    /** @var array(array(path => (string), emails => (string)), ...) */
    private $subscriptions;

    /** @var SimpleXMLElement */
    private $references;

    public function __construct(
        SimpleXMLElement $xml_repo,
        $extraction_path
    ) {
        $attrs = $xml_repo->attributes();
        $this->name = $attrs['name'];
        if(isset($attrs['dump-file'])) {
            $this->dump_file_path = $extraction_path . '/' . $attrs['dump-file'];
        }

        $this->access_file_contents = (string) $xml_repo->{"access-file"};

        $this->subscriptions = array();
        foreach($xml_repo->notification as $notif) {
            $a = $notif->attributes();
            $this->subscriptions[] = array(
                'path' => $a['path'],
                'emails' => $a['emails']
            );
        }

        $this->references = $xml_repo->references;
    }

    public function import(
        ImportConfig $configuration,
        Logger $logger,
        Project $project,
        RepositoryManager $repository_manager,
        SystemEventManager $system_event_manager,
        AccessFileHistoryCreator $accessfile_history_creator,
        MailNotificationManager $mail_notification_manager,
        RuleName $rule_name
    ) {
        if (! $rule_name->isValid($this->name)) {
            throw new XMLImporterException("Repository name '{$this->name}' is invalid: ".$rule_name->getErrorMessage());
        }

        $repo = new Repository ("", $this->name, '', '', $project);
        $sysevent = $repository_manager->create($repo, $system_event_manager);
        if (! $sysevent) {
            throw new XMLImporterException("Could not create system event");
        }

        $logger->info("[svn] Creating SVN repository {$this->name}");
        $sysevent->process();
        if ($sysevent->getStatus() != \SystemEvent::STATUS_DONE) {
            $logger->error($sysevent->getLog());
            throw new XMLImporterException("Event processing failed: status " . $sysevent->getStatus());
        } else {
            $logger->debug($sysevent->getLog());
        }

        $logger->info("[svn] Importing SVN repository {$this->name}");

        if (! empty($this->dump_file_path)) {
            $this->importCommits($logger, $repo);
        }

        if (! empty($this->access_file_contents)){
            $this->importAccessFile($logger, $repo, $accessfile_history_creator);
        }

        if (! empty($this->subscriptions)){
            $this->importSubscriptions($logger, $repo, $mail_notification_manager);
        }

        if (! empty($this->references)) {
            $this->importReferences($configuration, $logger, $repo);
        }
    }

    private function importCommits(Logger $logger, Repository $repo) {
        $rootpath_arg = escapeshellarg($repo->getSystemPath());
        $dumpfile_arg = escapeshellarg($this->dump_file_path);
        $sudo         = '';
        if (! $this->currentUserIsHTTPUser()) {
            $sudo = "-u ".escapeshellarg(ForgeConfig::get('sys_http_user'));
        }
        $commandline = "/usr/share/tuleap/plugins/svn/bin/import_repository.sh $sudo $rootpath_arg $dumpfile_arg";

        $logger->info("[svn {$this->name}] Import revisions: $commandline");

        try {
            $cmd = new \System_Command();
            $command_output = $cmd->exec($commandline);
            foreach($command_output as $line) {
                $logger->debug("[svn {$this->name}] svnadmin: $line");
            }
            $logger->debug("[svn {$this->name}] svnadmin returned with status 0");
        } catch (System_Command_CommandException $e) {
            foreach($e->output as $line) {
                $logger->error("[svn {$this->name}] svnadmin: $line");
            }
            $logger->error("[svn {$this->name}] svnadmin returned with status {$e->return_value}");
            throw new XMLImporterException(
                "failed to svnadmin load $dumpfile_arg in $rootpath_arg:".
                " exited with status {$e->return_value}");
        }
    }

    private function currentUserIsHTTPUser() {
        $http_user = posix_getpwnam(ForgeConfig::get('sys_http_user'));
        return ($http_user['uid'] === posix_getuid());
    }

    private function importAccessFile(
        Logger $logger,
        Repository $repo,
        AccessFileHistoryCreator $accessfile_history_creator)
    {
        // Add entry to history
        $access_file = $accessfile_history_creator->create(
            $repo,
            $this->access_file_contents,
            time());

        // Write .SVNAccessFile
        $writer = new SVN_AccessFile_Writer($repo->getSystemPath());
        $logger->info("[svn {$this->name}] Save Access File version #" . $access_file->getVersionNumber() . " to " . $writer->filename(). ": " . $access_file->getContent());
        if(!$writer->write_with_defaults($this->access_file_contents)) {
            throw new XMLImporterException("Could not write to " . $writer->filename());
        }
    }

    private function importSubscriptions(
        Logger $logger,
        Repository $repo,
        MailNotificationManager $mail_notification_manager)
    {
        foreach($this->subscriptions as $subscription) {
            $logger->info("[svn {$this->name}] Add subscription to {$subscription['path']}: {$subscription['emails']}");
            $notif = new MailNotification($repo, $subscription['emails'], $subscription['path']);
            $mail_notification_manager->create($notif);
        }
    }

    private function importReferences(ImportConfig $configuration, Logger $logger, Repository $repo)
    {
        EventManager::instance()->processEvent(
            Event::IMPORT_COMPAT_REF_XML,
            array(
                'logger'         => $logger,
                'created_refs'   => array(
                    'repository' => $repo,
                ),
                'service_name'   => self::SERVICE_NAME,
                'xml_content'    => $this->references,
                'project'        => $repo->getProject(),
                'configuration'  => $configuration,
            )
        );
    }
}
