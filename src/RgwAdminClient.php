<?php
namespace majorbio;

use PCextreme\RgwAdminClient\Client;

class RgwAdminClient
{
    public $rgw = null;

    public function __construct(array $rgw_config = [])
    {
        $this->rgw = new Client($rgw_config);
    }

    /**
     * 创建ceph用户
     *
     * @param string  $uid
     * @param string  $display_name
     * @param string  $email
     * @return array
     */
    public function createCephUser($uid, $display_name, $email)
    {
        $request = $this->rgw->createRequest('admin/user?', 'PUT', ['uid' => $uid, 'display-name' => $display_name, 'email' => $email]);//添加用户信息
        $response = $this->rgw->sendRequest($request);
        return json_decode(json_encode($response),true);
    }

    /**
     * 获取ceph用户
     *
     * @param string  $uid
     * @return array
     */
    public function getCephUser($uid = '')
    {
        $request = $this->rgw->createRequest('admin/user?', 'get', ['uid' => $uid]);//获取指定用户信息
        $response = $this->rgw->sendRequest($request);
        return json_decode(json_encode($response),true);
    }

    /**
     * 删除ceph用户
     *
     * @param string  $uid
     * @param bool    $purge_data
     * @return array
     */
    public function deleteCephUser($uid = '', $purge_data = true)
    {
        $request = $this->rgw->createRequest('admin/user?', 'DELETE', ['uid' => $uid, 'purge-data' => $purge_data]);//指定指定用户信息
        $response = $this->rgw->sendRequest($request);
        return json_decode(json_encode($response),true);
    }

    /**
     * 获取ceph用户
     *
     * @return array
     */
    public function getCephUserList()
    {
        $request = $this->rgw->createRequest('admin/user?', 'get', ['uid' => '']);
        $response = $this->rgw->sendRequest($request);
        return json_decode(json_encode($response),true);
    }
}