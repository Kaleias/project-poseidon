<?php

namespace App\Actions\Members;

use App\Models\Member;

class CreateMemberAction
{
    public function execute(array $data): Member
    {
        return Member::create($data);
    }
}
