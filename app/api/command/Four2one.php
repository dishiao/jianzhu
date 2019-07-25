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
 * @tips : 将 project主表 以及 子表 bid、permit、contract、finish 合成一张表
 * */
class Four2one extends Command
{
    const TOTAL_PROJECT_NUMBER = 21000000;

    protected function configure()
    {
        $this->setName('Four2one')->setDescription('this is four tables to one table command');
    }

    protected function execute(Input $input, Output $output)
    {
        /*
         * 初始化 日志
         * */
        Log::init([
            'type'  =>  'File',
            'path'  =>  APP_PATH.'/logs/'
        ]);
        /*
         * 跑之前 先清空表 jz_project_bcpf
         * */
        $sql = "TRUNCATE table jz_project_bcpf";
        Db::execute($sql);

        /*
         * 采用分割limit的方式来查询 SQL
         * 每次跑 1000 条
         * 目前总数 20425034条
         * */
        for ($i = 0; $i < self::TOTAL_PROJECT_NUMBER; $i = $i+1000){
            $sql = "SELECT
                    p.project_url as bcpf_project_url,
                    p.project_name as bcpf_project_name, 
                    p.project_area as bcpf_project_area,
                    p.project_unit as bcpf_project_unit,
                    p.project_type as bcpf_project_type,
                    p.project_nature as bcpf_project_nature,
                    p.project_use as bcpf_project_use,
                    p.project_allmoney as bcpf_project_allmoney,
                    p.project_acreage as bcpf_project_acreage,
                    p.project_level as bcpf_project_level,
                    
                    pb.bid_type as bcpf_bid_type,
                    pb.bid_way as bcpf_bid_way,
                    pb.bid_unitname as bcpf_bid_unitname,
                    pb.bid_date as bcpf_bid_date,
                    pb.bid_money as bcpf_bid_money,
                    pb.bid_area as bcpf_bid_area,
                    pb.bid_unitagency as bcpf_bid_unitagency,
                    pb.bid_pname as bcpf_bid_pname,
                    pb.bid_pnum as bcpf_bid_pnum,
                    pb.company_url as bcpf_company_url,
                    
                    pc.contract_type as bcpf_contract_type,
                    pc.contract_money as bcpf_contract_money,
                    pc.contract_signtime as bcpf_contract_signtime,
                    pc.contract_scale as bcpf_contract_scale,
                    pc.company_out_name as bcpf_company_out_name,
                    pc.contract_unitname as bcpf_contract_unitname,
                    pc.company_inpurl as bcpf_company_inpurl,
                    
                    pp.permit_money as bcpf_permit_money,
                    pp.permit_area as bcpf_permit_area,
                    pp.permit_certdate as bcpf_permit_certdate,
                    pp.permit_unitrcs as bcpf_permit_unitrcs,
                    pp.permit_unitdsn as bcpf_permit_unitdsn,
                    pp.permit_unitspv as bcpf_permit_unitspv,
                    pp.permit_unitcst as bcpf_permit_unitcst,
                    pp.company_rcsurl as bcpf_company_rcsurl,
                    pp.company_dsnurl as bcpf_company_dsnurl,
                    pp.company_spvurl as bcpf_company_spvurl,
                    pp.company_csturl as bcpf_company_csturl,
                    pp.permit_manager as bcpf_permit_manager,
                    pp.permit_managerid as bcpf_permit_managerid,
                    pp.permit_monitor as bcpf_permit_monitor,
                    pp.permit_monitorid as bcpf_permit_monitorid,
                    
                    pf.finish_money as bcpf_finish_money,
                    pf.finish_area as bcpf_finish_area,
                    pf.finish_realbegin as bcpf_finish_realbegin,
                    pf.finish_realfinish as bcpf_finish_realfinish,
                    pf.finish_unitdsn as bcpf_finish_unitdsn,
                    pf.finish_unitspv as bcpf_finish_unitspv,
                    pf.finish_unitcst as bcpf_finish_unitcst,
                    pf.company_dsnurl as bcpf_finish_company_dsnurl,
                    pf.company_spvurl as bcpf_finish_company_spvurl,
                    pf.company_csturl as bcpf_finish_company_csturl
                    
                FROM
                    jz_project p
                LEFT JOIN jz_project_bid pb ON p.project_url = pb.project_url
                LEFT JOIN jz_project_contract pc ON p.project_url = pc.project_url
                AND pb.company_url = pc.company_inpurl
                LEFT JOIN jz_project_permit_new pp ON (
                    p.project_url = pp.project_url
                    AND (
                        pb.company_url = pp.company_rcsurl
                        OR pb.company_url = pp.company_dsnurl
                        OR pb.company_url = pp.company_spvurl
                        OR pb.company_url = pp.company_csturl
                    )
                )
                LEFT JOIN jz_project_finish pf ON (
                    p.project_url = pf.project_url
                    AND (
                        pb.company_url = pf.company_dsnurl
                        OR pb.company_url = pf.company_spvurl
                        OR pb.company_url = pf.company_csturl
                    )
                ) limit {$i}, 1000 ";
            $res = Db::query($sql);
            if (empty($res)){
                continue;
            }
            $output->comment("start...{$i}");
            Log::info("start...the start cursor is {$i}");
            $this->praseAllData($res);
            $output->comment("end...{$i}");
            Log::info("end...the end cursor is {$i}");
        }
    }

    private function praseAllData($data){
        # init list
        # 初始化 map 对应关系
        $mapList = new constant();
        $map = $mapList->index();

        foreach ($data as $key=>$value){
            # parse column 解析数组中每列
            # 遍历每个字段为空则赋值为 空字符串
            $value = array_map(function ( $v ){
                return empty($v) ? '' : $v;
            },$value);

            # 有 map的 进行判断 并把string转换成对应的id,
            # 默认为0则表示没有对应关系
            $mapArr = [
                "project_type",
                "project_nature",
                "project_use",
                "project_level",
                "bid_type",
                "bid_way",
                "contract_type"
                ];
            foreach ($mapArr as $k=>$v){
                // 没有异常值( '' / None / - ) ,则进行对应关系处理,其余都归0
                if ($value['bcpf_'.$v] != '' && $value['bcpf_'.$v] != "None" && $value['bcpf_'.$v] != "-"){
                    $value['bcpf_'.$v] = $map[$v.'_map'][$value['bcpf_'.$v]];
                }
            }
            $res = Db::table("jz_project_bcpf")->insert($value);
            if ($res < 1){
                Log::error("this is insert failure".json_encode($value));
            }
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