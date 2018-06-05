<?php
namespace majorbio;

use Aws\S3\S3Client;

class Ceph
{
    public $s3 = null;

    public function __construct(array $config = [])
    {
        foreach (['endpoint', 'access_key', 'secret_key'] as $key) {
            if (!isset($config[$key])) {
                throw new \Exception("Ceph缺少配置项" . $key);
            }
        }
        $args = [
            'region' => '',
            'version' => '2006-03-01',
            'use_path_style_endpoint' => true,
            'endpoint' => $config['endpoint'],
            'credentials' => [
                'key' => $config['access_key'],
                'secret' => $config['secret_key'],
            ],
        ];
        try{
            $this->s3 = new S3Client($args);
        }catch(\Exception $e){
            throw new \Exception("Ceph客户端创建失败: " . $e->getMessage());
        }
    }
}
