<?php
namespace app\api\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\Log;
use app\api\constant;

/*
 * @author : dishiao
 * @tips : 将 project子表 bid、permit、contract、finish 分别进行压缩变V1
 * @command :
 *          项目根目录下
 *          php think.php list      -> 查看command列表以及帮助
 *          php think.php ProjectDataProcessV1  -> 运行
 * */
class ProjectDataProcessV1 extends Command
{
    # 项目表 总数
    const TOTAL_PROJECT_NUMBER = 2400000;
    # 查询项目数量时 slice 分隔数量
    const SLICE_NUMBER = 1;
    # 查询具体项目时 slice 分隔数量
    const SLICE_NUMBER_DETAIL = 1000;
    protected function configure()
    {
        $this->setName('ProjectDataProcessV1')->setDescription('this is four tables to V1 table command');
    }

    protected function execute(Input $input, Output $output)
    {
        ini_set ('memory_limit', '2048M');

        /*
         * 采用分割的方式来查询 SQL
         * */
        for ($i = 0; $i < self::TOTAL_PROJECT_NUMBER; $i = $i+self::SLICE_NUMBER){
            $output->comment("start...{$i}"." and times is ".date('Y-m-d h:i:s',time()));
            $time_start = time();

            $sql_project_url = "SELECT
                                    project_url
                                FROM
                                    jz_project 
                                WHERE id = {$i}";
            $res_project_url = Db::query($sql_project_url);
            if (empty($res_project_url)){
                continue;
            }
            $project_url = $res_project_url[0]['project_url'];
                $this->processData($project_url);
            }
            $output->comment("end...{$i}"." and times is ".date('Y-m-d h:i:s',time()));
            $time_stop = time();
            $output->comment("this round use time ".(((($time_stop-$time_start)%86400)%3600)%60)." s");
    }

    private function processData($project_url){
        if (isset($project_url) && !empty($project_url)){
            # process bid
            $sql_bid = "SELECT
                            id
                        FROM
                            `jz_project_bid`
                        WHERE
                            project_url = '{$project_url}'
                        GROUP BY
                            company_url,
                            bid_money,
                            bid_date";
            $res_bid = Db::query($sql_bid);
            if (!empty($res_bid)){
                $bid_arr = [];
                foreach ($res_bid as $key=>$value){
                    $bid_arr[] = $value['id'];
                }
                $bid_str = implode(",", $bid_arr);
                $sql_bid_detail = "SELECT
                                    *
                                FROM
                                    `jz_project_bid`
                                WHERE
                                    id in ({$bid_str})
                                ";
                $res_bid_detail = Db::query($sql_bid_detail);
                Db::table('jz_project_bid_v1')->insertAll($res_bid_detail);
            }
            # process contract
            $sql_contract = "SELECT
                            id
                        FROM
                            `jz_project_contract`
                        WHERE
                            project_url = '{$project_url}'
                        GROUP BY
                            company_inpurl,
                            contract_money,
                            contract_signtime";
            $res_contract = Db::query($sql_contract);
            if (!empty($res_contract)){
                $contract_arr = [];
                foreach ($res_contract as $key=>$value){
                    $contract_arr[] = $value['id'];
                }
                $contract_str = implode(",", $contract_arr);
                $sql_contract_detail = "SELECT
                                    *
                                FROM
                                    `jz_project_contract`
                                WHERE
                                    id in ({$contract_str})
                                ";
                $res_contract_detail = Db::query($sql_contract_detail);
                Db::table('jz_project_contract_v1')->insertAll($res_contract_detail);
            }
            # process permit
            $sql_permit = "SELECT
                            id
                        FROM
                            `jz_project_permit_new`
                        WHERE
                            project_url = '{$project_url}'
                        GROUP BY
                            permit_money,
                            company_rcsurl,
                            company_dsnurl,
                            company_spvurl,
                            company_csturl";
            $res_permit = Db::query($sql_permit);
            if (!empty($res_permit)){
                $permit_arr = [];
                foreach ($res_permit as $key=>$value){
                    $permit_arr[] = $value['id'];
                }
                $permit_str = implode(",", $permit_arr);
                $sql_permit_detail = "SELECT
                                    *
                                FROM
                                    `jz_project_permit_new`
                                WHERE
                                    id in ({$permit_str})
                                ";
                $res_permit_detail = Db::query($sql_permit_detail);
                Db::table('jz_project_permit_v1')->insertAll($res_permit_detail);
            }
            # process finish
            $sql_finish = "SELECT
                            id
                        FROM
                            `jz_project_finish`
                        WHERE
                            project_url = '{$project_url}'
                        GROUP BY
                            finish_realbegin,
                            finish_realfinish,
                            finish_money,
                            company_dsnurl,
                            company_spvurl,
                            company_csturl";
            $res_finish = Db::query($sql_finish);
            if (!empty($res_finish)){
                $finish_arr = [];
                foreach ($res_finish as $key=>$value){
                    $finish_arr[] = $value['id'];
                }
                $finish_str = implode(",", $finish_arr);
                $sql_finish_detail = "SELECT
                                    *
                                FROM
                                    `jz_project_finish`
                                WHERE
                                    id in ({$finish_str})
                                ";
                $res_finish_detail = Db::query($sql_finish_detail);
                Db::table('jz_project_finish_v1')->insertAll($res_finish_detail);
            }

        }
        else{
            return 0;
        }
    }

    /*
     * parse single column
     * */
    public function empty2string($v){
        if (empty($v)){
            return '';
        }else{
            return $v;
        }
    }
}