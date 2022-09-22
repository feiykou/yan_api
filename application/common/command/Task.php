<?php


namespace app\common\command;


use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

class Task extends Command
{
    protected function configure()
    {
        $this->setName('task')
            ->addArgument('action', Argument::OPTIONAL, "action")
            ->addArgument('force', Argument::OPTIONAL, "force");
    }

    protected function execute(Input $input, Output $output)
    {
        //获取输入参数
        $action = trim($input->getArgument('action'));
        $force = trim($input->getArgument('force'));

        $task = new \EasyTask\Task();
        $task->setPrefix('yanTask');
        $task->setRunTimePath('./runtime/');
        $task->addClass('\app\api\controller\v1\Customer', 'autoClearCustomerToPublic', 'customerPublic', 1, 1);
        $task->start();
//        // 根据命令执行
//        if ($action == 'start'){
//            $task->start();
//        } elseif ($action == 'status') {
//            $task->status();
//        } elseif ($action == 'stop') {
//            $force = ($force == 'force'); //是否强制停止
//            $task->stop($force);
//        } else {
//            exit('Command is not exist');
//        }
    }
}