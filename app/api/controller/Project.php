<?php
namespace app\api\controller;

use think\Controller;
use app\api\model\Project as ProjectModel;
use think\Db;

/**
 * 项目-相关controller
 */
class Project extends ApiBase
{
	/*
	*由于项目相关的筛选项都是在数据量极大的表里面,所以在此不单独列筛选项的controller，且把相应的筛选项直接写死在程序里面
	*/
	public function projectCondition(){
		$data_list = [];
		#筛选项-项目分类
		$data_list['project_type'][] = '房屋建筑工程';
		$data_list['project_type'][] = '市政工程';
		$data_list['project_type'][] = '其他';
		#筛选项-建设性质
		$data_list['project_nature'][] = '新建';
		$data_list['project_nature'][] = '改建';
		$data_list['project_nature'][] = '其他';
		$data_list['project_nature'][] = '迁建';
		$data_list['project_nature'][] = '扩建';
		$data_list['project_nature'][] = '恢复';
		#筛选项-工程用途
		$data_list['project_use'][] = '道路';
		$data_list['project_use'][] = '其他';
		$data_list['project_use'][] = '工业建筑';
		$data_list['project_use'][] = '居住建筑';
		$data_list['project_use'][] = '商住楼';
		$data_list['project_use'][] = '公共建筑';
		$data_list['project_use'][] = '科教文卫建筑';
		$data_list['project_use'][] = '桥隧';
		$data_list['project_use'][] = '交通运输类';
		$data_list['project_use'][] = '办公建筑';
		$data_list['project_use'][] = '公共交通';
		$data_list['project_use'][] = '农业建筑';
		$data_list['project_use'][] = '商业建筑';
		$data_list['project_use'][] = '排水';
		$data_list['project_use'][] = '风景园林';
		$data_list['project_use'][] = '给水';
		$data_list['project_use'][] = '居住建筑配套工程';
		$data_list['project_use'][] = '工业建筑配套工程';
		$data_list['project_use'][] = '公共建筑配套工程';
		$data_list['project_use'][] = '燃气';
		$data_list['project_use'][] = '旅游建筑';
		$data_list['project_use'][] = '热力';
		$data_list['project_use'][] = '环境园林';
		$data_list['project_use'][] = '农业建筑配套工程';
		$data_list['project_use'][] = '通信建筑';
		#筛选项-招标类型
		$data_list['bid_way'][]= '公开招标';
		$data_list['bid_way'][]= '邀请招标';
		$data_list['bid_way'][]= '直接委托';
		$data_list['bid_way'][]= '其他';
		#筛选项-合同类别
		$data_list['contract_type'][]= '施工总包';
		$data_list['contract_type'][]= '监理';
		$data_list['contract_type'][]= '设计';
		$data_list['contract_type'][]= '勘察';
		$data_list['contract_type'][]= '施工分包';
		$data_list['contract_type'][]= '工程总承包';
		$data_list['contract_type'][]= '专业承包';
		$data_list['contract_type'][]= '施工劳务';
		$data_list['contract_type'][]= '项目管理';
		$data_list['contract_type'][]= '设计施工一体化';
		$res['code'] = 1;
		$res['msg'] = 'success';
		$res['data'] = $data_list;
		$res = json_encode($res);
		return $res;
	}
	# 项目数据查询详细
    # 注意！详情和不能复用数量的代码因为详情是以项目为维度，数量是以公司数量为维度
    public function getProjectDataDetail($params){
        $params_arr = $this->transfromGet($params);
        $res = ProjectModel::getProjectDataDetail($params_arr);
        $result['key'] = 'project'; # 代表以项目为主，显示为项目的模板显示
        $result['code'] = 1;
        $result['msg'] = 'success';
        $result['data'] = $res;
        $result = json_encode($result);
        return $result;
    }
	#项目数据查询数量
    public function getProjectDataNum($params){
	    $res = $this->getProjectData($params);
	    if (empty($params)){
			return 'no params,no data';
		}
	    $result['code'] = 1;
	    $result['msg'] = 'success';
	    $result['count'] = count($res);
	    $result = json_encode($result);
	    return $result;
    }
	#项目数据查询得到company_url
	public function getProjectData($params){
		if (empty($params)){
			return 'no params,no data';
		}
		#判断并处理参数
		$params_arr = $this->transfromGet($params);
		/*
		 * 查项目相关数量的时候 兼容到 keywords_contract_scale
		 * 如果有，则contract = 1
		 * 不使用transfromGet 方法里面进行处理的原因是，此方法是公用方法会影响到详情
		 * */
        $params_keys = array_keys($params);
        if (in_array("keywords_contract_scale",$params_keys)){
            $params_arr['contract'] = 1;
        }

		#根据参数情况和值，查询出符合条件的数据
        // 注释这段是方法改成了 V1 新版,如果不合适,可以随时切回.
//        $res = ProjectModel::getProjectData($params_arr);
        $res = ProjectModel::getProjectDataV1($params_arr);
        $res = $this->delByValue($res,'None');
        return $res;
	}
	#处理参数
	protected function transfromGet($params){
		# 基础条件字段
		$basic_arr = ['project_type','project_nature','project_use'];
		# 招投标条件字段
		$bid_arr = ['bid_way','bid_money','bid_date_start','bid_date_end'];
		# 合同备案条件字段
		$contract_arr = ['contract_type','contract_money','contract_scale','contract_date_start','contract_date_end'];
		# 竣工验收备案条件字段
		$finish_arr = ['finish_money','finish_area','finish_realbegin_start','finish_realbegin_end','finish_realfinish_start','finish_realfinish_end'];
		$params['bid'] = 0;
		$params['contract'] = 0;
		$params['finish'] = 0;
		$params_keys = array_keys($params);
		$params_count = count($params_keys);
		for ($i=0; $i < $params_count; $i++) {
			if (array_search($params_keys[$i], $bid_arr) !== false) {
				$params['bid'] = 1;
			}
			if (array_search($params_keys[$i], $contract_arr) !== false) {
				$params['contract'] = 1;
			}
			if (array_search($params_keys[$i], $finish_arr) !== false) {
				$params['finish'] = 1;
			}
		}
		return $params;
	}

	# 删除数组中指定value
    function delByValue($arr, $value){
        if(!is_array($arr)){
            return $arr;
        }
        foreach($arr as $k=>$v){
            if($v == $value){
                unset($arr[$k]);
            }
        }
        return $arr;
    }

    # 项目数据导出
    function exportPeoject($params){
	    $params['is_limit'] = false;
        $params_arr = $this->transfromGet($params);
        $list = ProjectModel::getProjectDataDetail($params_arr);
        $titles =
            "项目名称,项目分类,建设性质,工程用途,总投资,总面积,
            招标类型,招标方式,中标单位名称,中标日期,中标金额(万元),面积（平方米）,招标代理单位名称,网站招投标详情页面,
            合同类别,合同金额（万元）,合同签订日期,建设规模,承包单位,网站合同备案详情页面,
            实际造价（万元）,实际面积（平方米）,实际开工日期,实际竣工验收日期,设计单位,监理单位,施工单位,网站竣工验收备案详情页面";
        $keys   =
            "project_name,project_type,project_nature,project_use,project_allmoney,project_acreage,
            bid_type,bid_way,bid_unitname,bid_date,bid_money,bid_area,bid_unitagency,bid_url,
            contract_type,contract_money,contract_signtime,contract_scale,contract_unitname,contract_add_url,
            finish_money,finish_area,finish_realbegin,finish_realfinish,finish_unitdsn,finish_unitspv,finish_unitcst,finish_add_url";
        $path = export_excel($titles, $keys, $list, '项目');
        return $this->apiReturn(['path'=>$path]);
    }
}
?>