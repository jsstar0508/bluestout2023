<?php
namespace WPIDE\App\Tasks;

use ActionScheduler_DBStore;
use ActionScheduler_Store;
use Exception;
use WPIDE\App\App;

abstract class Task
{
    protected static $option_key = 'wpide_tasks';
    protected static $store;

    public static function register()
    {

        add_action(self::name(), [get_called_class(), 'handler'], 10, 2);
    }

    /**
     * @throws Exception
     */
    public static function dispatch($args): array
    {
        if(!function_exists('as_enqueue_async_action')) {
            throw new Exception('Cannot dispatch '.self::name().' task! Action Scheduler library was not found!');
        }

        $task_id = uniqid();
        $action_id = as_enqueue_async_action(self::name(), [$task_id, $args ]);

        error_log( '#'.$task_id.': Task Dispatched: ' . self::name());
        error_log( '#'.$task_id.': Task Args: ' . json_encode($args));

        self::add($task_id, $action_id);

        return [
            'task_id' => $task_id,
            'action_id' => $action_id
        ];
    }

    protected static function queueStore(): ActionScheduler_DBStore
    {
        if(empty(self::$store)) {
            self::$store = new ActionScheduler_DBStore();
        }

        return self::$store;
    }

    public static function getStatusLabels(): array
    {

        $store = self::queueStore();

        return $store->get_status_labels();
    }

    public static function getQueue(): array
    {

        $queue =  get_option(self::$option_key, []);

        $store = self::queueStore();
        $labels = self::getStatusLabels();

        $completed_actions = $store->query_actions([
            'hook' => self::name(),
            'status' => ActionScheduler_Store::STATUS_COMPLETE
        ]);
        $running_actions = $store->query_actions([
            'hook' => self::name(),
            'status' => ActionScheduler_Store::STATUS_RUNNING
        ]);
        $pending_actions = $store->query_actions([
            'hook' => self::name(),
            'status' => ActionScheduler_Store::STATUS_PENDING
        ]);
        $cancelled_actions = $store->query_actions([
            'hook' => self::name(),
            'status' => ActionScheduler_Store::STATUS_CANCELED
        ]);
        $failed_actions = $store->query_actions([
            'hook' => self::name(),
            'status' => ActionScheduler_Store::STATUS_FAILED
        ]);

        $updated = 0;
        $deleted = [];
        $queue = array_map(function($item) use (&$updated, &$deleted, $labels, $failed_actions, $cancelled_actions, $pending_actions, $running_actions, $completed_actions) {

            if(in_array($item['action_id'], $completed_actions)) {
                $status = ActionScheduler_Store::STATUS_COMPLETE;
            }else if(in_array($item['action_id'], $running_actions)) {
                $status = ActionScheduler_Store::STATUS_RUNNING;
            }else if(in_array($item['action_id'], $pending_actions)) {
                $status = ActionScheduler_Store::STATUS_PENDING;
            }else if(in_array($item['action_id'], $cancelled_actions)) {
                $status = ActionScheduler_Store::STATUS_CANCELED;
            }else if(in_array($item['action_id'], $failed_actions)) {
                $status = ActionScheduler_Store::STATUS_FAILED;
            }else{
                $status = $item['status'];
                $deleted[] = $item['task_id'];
            }

            if($item['status'] !== $status) {
                $item['status'] = $status;
                $item['statusLabel'] = $labels[$status];
                $updated++;
            }

            return $item;

        }, $queue);

        if(count($deleted) > 0) {
            $queue = array_filter($queue, function($item) use($deleted) {
                return !in_array($item['task_id'], $deleted);
            });
        }

        if($updated > 0 || count($deleted) > 0) {
            self::saveQueue($queue);
        }

        return $queue;
    }

    public static function saveQueue($queue = []) {
        update_option(self::$option_key, $queue);
    }

    public static function add($task_id, $action_id) {

        $queue = self::getQueue();

        $labels = self::getStatusLabels();

        $queue[$task_id] = [
            'name' => self::niceName(),
            'task_id' => $task_id,
            'action_id' => $action_id,
            'status' => ActionScheduler_Store::STATUS_PENDING,
            'statusLabel' => $labels[ActionScheduler_Store::STATUS_PENDING],
            'return' => null
        ];

        self::saveQueue($queue);
    }

    public static function remove($task_id, $action_id) {

        $store = self::queueStore();
        $store->delete_action($action_id);

        $queue = self::getQueue();
        if(isset($queue[$task_id])) {
            unset($queue[$task_id]);
            self::saveQueue($queue);
        }
    }

    public static function update($task_id, $args): bool
    {

        $queue = self::getQueue();

        if(isset($queue[$task_id])) {
            $queue[$task_id] = array_merge($queue[$task_id], $args);
            self::saveQueue($queue);
            return true;
        }

        return false;
    }

    public static function name(): string
    {
        return get_called_class();
    }

    public static function niceName()
    {
        $array = explode('\\', self::name());
        return array_pop($array);
    }

    abstract public static function handle($task_id, $args);

    public static function handler($task_id, $args = []) {

        if(!defined('WPIDE_DOING_TASK')) {
            define('WPIDE_DOING_TASK', true);
        }

        error_log( '#'.$task_id.': Task Started: ' . self::name());
        error_log( '#'.$task_id.': Task Args: ' . json_encode($args));

        App::instance()->bootstrap();
        $return  = call_user_func([get_called_class(), 'handle'], $task_id, $args);

        self::update($task_id, [
            'return' => $return
        ]);

        error_log( '#'.$task_id.': Task Completed: ' . self::name());
    }
}