<?php
namespace WPIDE\App\Tasks;

class TestTask extends Task
{

    public static function handle($task_id, $args): bool
    {
        // Handle task
        return true;
    }
}