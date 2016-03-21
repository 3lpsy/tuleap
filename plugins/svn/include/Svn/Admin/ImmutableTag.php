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
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */
namespace Tuleap\Svn\Admin;

use Project;
use Tuleap\Svn\Repository\Repository;

class ImmutableTag {

    private $paths;
    private $repository;
    private $whitelist;

    public function __construct(Repository $repository, $paths, $whitelist) {
        $this->repository = $repository;
        $this->paths      = $paths;
        $this->whitelist  = $whitelist;
    }

    public function getPaths() {
        return $this->paths;
    }

    public function getRepository(){
        return $this->repository;
    }

    public function getWhitelist(){
        return $this->whitelist;
    }
}