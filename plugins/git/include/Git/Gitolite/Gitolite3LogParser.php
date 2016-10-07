<?php
/**
 * Copyright (c) Enalean, 2016. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>
 */

namespace Tuleap\Git\Gitolite;

use DateTime;
use DirectoryIterator;
use Git;
use GitRepositoryFactory;
use GitRepositoryGitoliteAdmin;
use Logger;
use System_Command;
use Tuleap\Git\History\Dao;
use Tuleap\Git\RemoteServer\Gerrit\HttpUserValidator;
use UserManager;

class Gitolite3LogParser
{
    const REPOSITORY_PATH = 'gitolite/repositories/';
    const GIT_COMMAND     = 'pre_git';
    const FILE_NAME       = 'gitolite-';
    const FILE_EXTENSION  = '.log';


    /** @var Logger */
    private $logger;

    /** @var System_Command */
    private $system_command;

    /**
     * @var HttpUserValidator
     */
    private $user_validator;

    /**
     * @var Dao
     */
    private $history_dao;

    /**
     * @var GitRepositoryFactory
     */
    private $repository_factory;

    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var GitoliteFileLogsDao
     */
    private $file_logs_dao;

    public function __construct(
        Logger $logger,
        System_Command $system_command,
        HttpUserValidator $user_validator,
        Dao $history_dao,
        GitRepositoryFactory $repository_factory,
        UserManager $user_manager,
        GitoliteFileLogsDao $file_logs_dao
    ) {
        $this->logger             = $logger;
        $this->system_command     = $system_command;
        $this->user_validator     = $user_validator;
        $this->history_dao        = $history_dao;
        $this->repository_factory = $repository_factory;
        $this->user_manager       = $user_manager;
        $this->file_logs_dao      = $file_logs_dao;
    }

    public function parseAllLogs($path)
    {
        $iterator = new DirectoryIterator($path);
        foreach ($iterator as $file) {
            if (! $file->isDot() && preg_match('/^gitolite-\d{4}-\d{2}.log$/', $file->getFilename())) {
                $this->parseLogs($path . $file);
            }
        }
    }

    public function parseCurrentAndPreviousMonthLogs($path)
    {
        $last_month_file    = self::FILE_NAME . date("Y-m", strtotime("-1 month")) . self::FILE_EXTENSION;
        $current_month_file = self::FILE_NAME . date('Y-m') . self::FILE_EXTENSION;

        $this->parseLogs($path . $last_month_file);
        $this->parseLogs($path . $current_month_file);
    }

    public function parseLogs($log)
    {
        if (file_exists($log)) {
            $log_file = fopen("$log", "r");
            if (! $log_file) {
                throw new CannotAccessToGitoliteLogException();
            } else {
                $last_read_char = $this->file_logs_dao->getLastReadLine($log);
                fseek($log_file, $last_read_char['end_line']);
                while (! feof($log_file)) {
                    $log_line = fgetcsv($log_file, 0, "\t");
                    if ($log_line !== false) {
                        $this->parseLine($log_line, $log);
                    }
                }

                $this->file_logs_dao->storeLastLine($log, ftell($log_file));
                fclose($log_file);
            }
        }
    }

    private function parseLine(array $line, $filename)
    {
        if ($this->isAReadAccess($line) && $this->isNotASystemUser($line[4])) {
            $this->logger->debug(
                'File ' . $filename . '. Add one Read access for repository ' . $line[3] . ' pattern ' . $line[7] . ' for user ' . $line[4]
            );

            $repository = $this->repository_factory->getFromFullPath(
                self::REPOSITORY_PATH . $line[3] . '.git'
            );

            $user = $this->user_manager->getUserByUserName($line[4]);
            $day  = DateTime::createFromFormat('Y-m-d.H:i:s', $line[0]);

            $user->getId();
            $this->history_dao->insertGitPhpView($repository->getId(), $user->getId(), $day->getTimestamp());
        }
    }

    private function isAReadAccess(array $line)
    {
        return $line[2] === self::GIT_COMMAND && $line[5] === Git::READ_PERM;
    }

    private function isNotASystemUser($user)
    {
        return $user !== GitRepositoryGitoliteAdmin::USERNAME && ! $this->user_validator->isLoginAnHTTPUserLogin($user);
    }
}
