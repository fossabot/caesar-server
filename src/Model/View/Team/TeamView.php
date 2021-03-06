<?php

declare(strict_types=1);

namespace App\Model\View\Team;

class TeamView
{
    /**
     * @var string
     */
    public $id;
    /**
     * @var string
     */
    public $title;
    /**
     * @var MemberShortView[]
     */
    public $users = [];
    /**
     * @var ListView[]
     */
    public $lists;

    /**
     * @var string
     */
    public $icon;

    /**
     * @var string
     */
    public $type;
}
