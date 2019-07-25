<?php
namespace app\api\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

/*
 * test ：
 * php think.php test
 * */

class Test extends Command
{
    protected function configure()
    {
        $this->setName('test')->setDescription('Here is the remark ');
    }

    protected function execute(Input $input, Output $output)
    {
        $sql = "select * from jz_project limit 1";
        $res = Db::query($sql);
        dd($res);
        $output->writeln("TestCommand:");
    }
}