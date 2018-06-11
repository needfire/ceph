<?php
namespace majorbio;

use Aws\S3\S3Client;
use Aws\S3\MultipartUploader;
use Aws\Exception\MultipartUploadException;

//https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html
//https://github.com/awsdocs/aws-doc-sdk-examples/tree/master/php/example_code/s3

class Ceph
{
    public $s3 = null;

    public function __construct(array $config = [])
    {
        foreach (['endpoint', 'access_key', 'secret_key'] as $key) {
            if ( ! isset($config[$key])) {
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
        try {
            $this->s3 = new S3Client($args);
        } catch (\Exception $e) {
            throw new \Exception("Ceph客户端创建失败: " . $e->getMessage());
        }
    }

    /**
     * 创建容器
     *
     * @param array $args = [Bucket='xxx'[, ACL]]
     *
     * @return bool|null
     */
    public function createBucket($args = [])
    {
        try {
            if ( ! isset($args['Bucket'])) { return false; }
            $this->s3->createBucket($args)->toArray();
            return $this->existBucket(['Bucket' => $args['Bucket']]);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 删除容器
     *
     * @param array $args = [Bucket='xxx']
     *
     * @return bool|null
     */
    public function deleteBucket($args = [])
    {
        try {
            if ( ! isset($args['Bucket'])) { return true; }
            $this->s3->deleteBucket($args)->toArray();
            $exist = $this->existBucket(['Bucket' => $args['Bucket']]);
            if (is_null($exist)) {
                return $exist;
            } else {
                return !$exist;
            }
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 容器是否存在
     *
     * @param array $args = [Bucket='xxx']
     *
     * @return bool|null
     */
    public function existBucket($args = [])
    {
        try {
            if ( ! isset($args['Bucket'])) { return false; }
            $result = $this->s3->headBucket($args)->toArray();
            if (isset($result['@metadata']['statusCode']) && $result['@metadata']['statusCode'] == '200') {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 容器列表详细信息
     *
     * @param array $args
     *
     * @return array|null
     */
    public function listBuckets($args = [])
    {
        try {
            return $this->s3->listBuckets($args)->toArray();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 容器列表<只包含容器名称>
     *
     * @param array $args
     *
     * @return array|null
     */
    public function listBucketsNames($args = [])
    {
        try {
            $buckets = $this->s3->listBuckets($args)->toArray();
            return array_column($buckets['Buckets'], 'Name');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 得到容器ACL
     *
     * @param array $args = [Bucket='xxx']
     *
     * @return mixed
     */
    public function getBucketAcl($args = [])
    {
        try {
            if ( ! isset($args['Bucket'])) { return false; }
            return $this->s3->getBucketAcl($args)->toArray();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 对象是否存在
     *
     * @param array $args = [Bucket, Key]
     *
     * @return bool|null
     */
    public function existObject($args = [])
    {
        try {
            if ( ! isset($args['Bucket'])) { return false; }
            if ( ! isset($args['Key'])) { return false; }
            $result = $this->s3->headObject($args)->toArray();
            if (isset($result['@metadata']['statusCode']) && $result['@metadata']['statusCode'] == '200') {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 创建对象
     *
     * @param array $args = [
     *                  Bucket = <string>,
     *                  Key = <string>,
     *                  Body = <string || resource || Psr\Http\Message\StreamInterface>
     *                  [ ACL = 'private|public-read|public-read-write|authenticated-read|aws-exec-read|bucket-owner-read|bucket-owner-full-control' ]
     *              ]
     *
     * @return string|null
     */
    public function createObject($args = [])
    {
        try {
            if ( ! isset($args['Body']) && ! isset($args['SourceFile'])) { return false; }
            if ( ! isset($args['Bucket'])) { return false; }
            if ( ! isset($args['Key'])) { return false; }
            $this->s3->putObject($args)->toArray();
            return $this->existObject([
                'Bucket' => $args['Bucket'],
                'Key' => $args['Key']
            ]);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 对象列表
     *
     * @param array $args = [Bucket]
     *
     * @return array|null
     */
    public function listObjects($args = [])
    {
        try {
            if ( ! isset($args['Bucket'])) { return []; }
            return $this->s3->listObjects($args)->toArray();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 对象列表<只包含容器名称>
     *
     * @param array $args = [Bucket]
     *
     * @return array|null
     */
    public function listObjectsNames($args = [])
    {
        try {
            if ( ! isset($args['Bucket'])) { return []; }
            $objects = $this->s3->listObjects($args)->toArray();
            return array_column($objects['Contents'], 'Key');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 列出容器中的文件夹、对象
     *
     * @param array $args = [Bucket, Dir]
     *
     * @return mixed
     */
    public function listFolderFile($args = [])
    {
        if ( ! isset($args['Bucket'])) { return []; }
        $args['Bucket'] = trim($args['Bucket']);
        if ( ! isset($args['Dir'])) { $args['Dir'] = ''; }
        $args['Dir'] = trim($args['Dir']);

        $as = [];

        //取数据 - 没指定文件夹
        if($args['Dir'] == '')
        {
            $all = $this->listObjects($args);
            if(isset($all['Contents']) && !empty($all['Contents'])){
                foreach($all['Contents'] as $a){
                    $a['LastModified'] = $a['LastModified']->__toString();
                    $as[] = $a;
                }
            }
        }
        //取数据 - 指定了文件夹
        else
        {
            $last = mb_substr($args['Dir'], -1, 1, 'utf-8');
            if('/' != $last) { $args['Dir'] .= '/'; }
            $objects = $this->s3->getIterator('ListObjects', array(
                "Bucket" => $args['Bucket'],
                "Prefix" => $args['Dir']
            ));
            foreach ($objects as $object) {
                $object['LastModified'] = $object['LastModified']->__toString();
                $as[] = $object;
            }
        }

        //处理数据
        return $this->dataToFolderFile($args['Dir'], $as);
    }

    /**
     * 列出容器中的文件夹、对象
     *
     * @param string $current_dir_path = 'dir1/dir1_1/...'
     * @param array  $ds = [['Key'=>'dir1/dir1_1/he.txt'], ['Key'=>'dir1/dir1_1/ge.txt'],...]
     *
     * @return mixed
     */
    private function dataToFolderFile($current_dir_path='', $ds=[])
    {
        $current_dir_path = trim($current_dir_path);
        $current_dir_array = ($current_dir_path != '') ? explode('/', $current_dir_path) : [];
        $current_dir_array = array_filter($current_dir_array);//过滤掉空值
        //先把 current_dir 前缀去掉
        if( ! empty($current_dir_array))
        {
            $dir_string = implode('/', $current_dir_array).'/';
            foreach($ds as &$d)
            {
                //以 $dir_string 开头
                if(mb_strpos($d['Key'], $dir_string) === 0)
                {
                    $d['Key'] = mb_substr($d['Key'], mb_strlen($dir_string));
                }
            }
            unset($d);
        }
        //准备文件夹
        $rs_files = $rs_folders = $had_folders = [];
        //处理 文件 和 文件夹
        $cd = empty($current_dir_array) ? '' : implode('/', $current_dir_array).'/';
        foreach($ds as $d)
        {
            $d['Dir'] = $cd;
            if(false === strpos($d['Key'], '/'))
            {
                $d['_folder_or_file_'] = 'file';
                $rs_files[] = $d;
            }
            else
            {
                $d['_folder_or_file_'] = 'folder';
                $t = explode('/', $d['Key']);
                if( ! in_array($t[0], $had_folders)){
                    $d['Key'] = $t[0];
                    $rs_folders[] = $d;
                    $had_folders[] = $t[0];//记录一下
                }
            }
        }
        return rs(0, 'ok', ['dts'=>array_merge($rs_folders, $rs_files), 'dir_array'=>$current_dir_array]);
    }

    /**
     * 删除对象
     *
     * @param array $args = [Bucket, Key]
     *
     * @return bool|null
     */
    public function deleteObject($args = [])
    {
        try {
            if ( ! isset($args['Bucket'])) { return false; }
            if ( ! isset($args['Key'])) { return false; }
            $this->s3->deleteObject($args)->toArray();
            $exist = $this->existObject([
                'Bucket' => $args['Bucket'],
                'Key' => $args['Key']
            ]);
            if (is_null($exist)) {
                return $exist;
            } else {
                return !$exist;
            }
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 生成签名下载地址
     *
     * @param array $args = [Bucket, Key, expire(秒)]
     * @param bool  $only_url
     *
     * @return string|null
     */
    public function createPresignedRequest($args=[], $only_url=true)
    {
        try {
            $command = $this->s3->getCommand(
                'GetObject',
                [
                    'Bucket' => (string)$args['Bucket'],
                    'Key' => (string)$args['Key']
                ]
            );
            $expire = intval($args['expire']);
            $request = $this->s3->createPresignedRequest($command, "+$expire seconds");
            return true === $only_url ? (string)$request->getUri() : $request;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 得到对象ACL
     *
     * @param array $args = [Bucket, Key]
     *
     * @return mixed
     */
    public function getObjectAcl($args = [])
    {
        try {
            if ( ! isset($args['Bucket'])) { return false; }
            if ( ! isset($args['Key'])) { return false; }
            return $this->s3->getObjectAcl($args)->toArray();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 创建对象
     *
     * @param array $args = [
     *                  Bucket = <string>,
     *                  Key = <string>,
     *                  Source = <file_path>
     *              ]
     * @param bool  $full
     *
     * @return mixed
     */
    public function multipartUpload($args=[], $full=true)
    {
        if ( ! isset($args['Bucket'])) { return false; }
        if ( ! isset($args['Key'])) { return false; }
        if ( ! isset($args['Source'])) { return false; }
        $uploader = new MultipartUploader($this->s3, $args['Source'], ['bucket'=>$args['Bucket'], 'key'=>$args['Key']]);
        try {
            set_time_limit(0);
            $result = $uploader->upload();
            return true === $full ? $result : $result['ObjectURL'];
        } catch (MultipartUploadException $e) {
            return $e->getMessage();
        }
    }

    /**
     * 创建对象<暂时只能账号内复制>
     *
     * @param array $args = [
     *                  DestinationBucket = <string>,
     *                  DestinationKey = <string>,
     *                  CopySource = <string>
     *              ]
     * @return mixed
     */
    public function copyObject($args = [])
    {
        try {
            if( ! isset($args['DestinationBucket'])){ return false; }
            if( ! isset($args['DestinationKey'])){ return false; }
            if( ! isset($args['CopySource'])){ return false; }
            $this->s3->copyObject([
                'Bucket' => $args['DestinationBucket'],
                'Key' => $args['DestinationKey'],
                'CopySource' => $args['CopySource'],
            ]);
            return $this->existObject([
                'Bucket' => $args['DestinationBucket'],
                'Key' => $args['DestinationKey']
            ]);
        } catch (\Exception $e) {
            return null;
        }
    }
}
