<?php
namespace majorbio;

use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Aws\S3\MultipartUploader;
use Aws\Exception\MultipartUploadException;

//https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html
//https://github.com/awsdocs/aws-doc-sdk-examples/tree/master/php/example_code/s3

/*if( ! function_exists('rs'))
{
    function rs($c=0, $m='', $d=[])
    {
        $rt = [];
        $rt['c'] = intval($c);
        $rt['m'] = $m;
        $rt['d'] = $d;
        return $rt;
    }
}*/

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
        $this->s3 = new S3Client($args);
    }

    /**
     * 创建容器
     *
     * @param array $args = [Bucket='xxx'[, ACL]]
     *
     * @return array
     */
    public function createBucket($args = [])
    {
        try {
            if ( ! isset($args['Bucket'])) { return rs(1, '缺少Bucket名称'); }
            $this->s3->createBucket($args)->toArray();
            $exist = $this->existBucket(['Bucket'=>$args['Bucket']]);
            if($exist){
                return rs(0, '创建成功', ['Bucket'=>$args['Bucket']]);
            }else{
                return rs(1, '缺少Bucket名称');
            }
        } catch (AwsException $e) {
            $err = $e->getMessage();
            if(mb_strpos($err, 'InvalidAccessKeyId') !== false){
                $msg = 'access_key错误';
            }elseif(mb_strpos($err, 'SignatureDoesNotMatch') !== false){
                $msg = 'secret_key错误';
            }else{
                $msg = 'endpoint错误';
            }
            return rs(1, $msg);
        }
    }

    /**
     * 删除容器
     *
     * @param array $args = [Bucket='xxx']
     *
     * @return array
     */
    public function deleteBucket($args = [])
    {
        try {
            if ( ! isset($args['Bucket'])) { return rs(1, '缺少Bucket名称'); }
            $this->s3->deleteBucket($args)->toArray();
            $exist = $this->existBucket(['Bucket' => $args['Bucket']]);
            if ($exist) {
                return rs(1, '没有删除成功');
            } else {
                return rs(0, '删除成功');
            }
        } catch (S3Exception $e) {
            $msg = '系统错误';
            $err = $e->getMessage();
            if(mb_strpos($err, 'NoSuchBucket') !== false){
                $msg = '不存在此容器';
            }elseif(mb_strpos($err, 'AccessDenied') !== false){
                $msg = '没有权限删除此容器';
            }
            return rs(1, $msg);
        }
    }

    /**
     * 容器是否存在
     *
     * @param array $args = [Bucket='xxx']
     *
     * @return bool
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
            return false;
        }
    }

    /**
     * 容器列表详细信息
     *
     * @param array $args
     *
     * @return array
     */
    public function listBuckets($args = [])
    {
        try {
            $buckets = $this->s3->listBuckets($args)->toArray();
            return rs(0, 'ok', ['buckets'=>$buckets]);
        } catch (\Exception $e) {
            return rs(0, 'ok', ['buckets'=>[]]);
        }
    }

    /**
     * 容器列表<只包含容器名称>
     *
     * @param array $args
     *
     * @return array
     */
    public function listBucketsNames($args = [])
    {
        try {
            $buckets = $this->s3->listBuckets($args)->toArray();
            $result = [];
            if( ! is_null($buckets) && isset($buckets['Buckets']) && is_array($buckets['Buckets']) && count($buckets['Buckets'])>0){
                $result = array_column($buckets['Buckets'], 'Name');;
            }
            return rs(0, 'ok', ['buckets'=>$result]);
        } catch (\Exception $e) {
            return rs(0, 'ok', ['buckets'=>[]]);
        }
    }

    /**
     * 得到容器ACL
     *
     * @param array $args = [Bucket='xxx']
     *
     * @return mixed
     */
    /*public function getBucketAcl($args = [])
    {
        try {
            if ( ! isset($args['Bucket'])) { return false; }
            return $this->s3->getBucketAcl($args)->toArray();
        } catch (\Exception $e) {
            return null;
        }
    }*/

    /**
     * 对象是否存在
     *
     * @param array $args = [Bucket, Key]
     *
     * @return bool
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
            return false;
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
     * @return array
     */
    public function createObject($args = [])
    {
        try {
            if ( ! isset($args['Body']) && ! isset($args['SourceFile'])) { return rs(0, '缺少内容'); }
            if ( ! isset($args['Bucket'])) { return rs(0, '缺少Bucket名称'); }
            if ( ! isset($args['Key'])) { return rs(0, '缺少Key名称'); }
            $this->s3->putObject($args)->toArray();
            $exist = $this->existObject(['Bucket'=>$args['Bucket'], 'Key'=>$args['Key']]);
            if($exist){
                return rs(0, '创建成功', ['Bucket'=>$args['Bucket']]);
            }else{
                return rs(1, '创建成功失败');
            }
        } catch (\Exception $e) {
            $err = $e->getMessage();
            if(mb_strpos($err, 'InvalidAccessKeyId') !== false){
                $msg = 'access_key错误';
            }elseif(mb_strpos($err, 'SignatureDoesNotMatch') !== false){
                $msg = 'secret_key错误';
            }else{
                $msg = 'endpoint错误';
            }
            return rs(1, $msg);
        }
    }

    /**
     * 对象列表
     *
     * @param array $args = [Bucket]
     *
     * @return array
     */
    public function listObjects($args = [])
    {
        try {
            if ( ! isset($args['Bucket'])) { return rs(1, '缺少Bucket名称', ['objects'=>[]]); }
            $objects = $this->s3->listObjects($args)->toArray();
            return rs(0, 'ok', ['objects'=>$objects]);
        } catch (\Exception $e) {
            return rs(1, '系统错误', ['objects'=>[]]);
        }
    }

    /**
     * 对象列表<只包含容器名称>
     *
     * @param array $args = [Bucket]
     *
     * @return array
     */
    public function listObjectsNames($args = [])
    {
        try {
            if ( ! isset($args['Bucket'])) { return rs(1, '缺少Bucket名称', ['objects'=>[]]); }
            $objects = $this->s3->listObjects($args)->toArray();
            $result = [];
            if(!is_null($objects) && isset($objects['Contents']) && is_array($objects['Contents']) && count($objects['Contents'])>0){
                $result = array_column($objects['Contents'], 'Key');
            }
            return rs(0, 'ok', ['objects'=>$result]);
        } catch (\Exception $e) {
            return rs(1, '系统错误', ['objects'=>[]]);
        }
    }

    /**
     * 列出容器中的文件夹、对象
     *
     * @param array $args = [Bucket, Dir]
     *
     * @return array
     */
    public function listFolderFile($args = [])
    {
        if ( ! isset($args['Bucket'])) { return rs(1, '缺少Bucket名称', ['objects'=>[]]); }
        $args['Bucket'] = trim($args['Bucket']);
        if ( ! isset($args['Dir'])) { $args['Dir'] = ''; }
        $args['Dir'] = trim($args['Dir']);

        $as = [];

        //取数据 - 没指定文件夹
        if($args['Dir'] == '')
        {
            $all = $this->listObjects($args);
            if(isset($all['d']['objects']['Contents']) && !empty($all['d']['objects']['Contents'])){
                foreach($all['d']['objects']['Contents'] as $a){
                    /** @noinspection PhpUndefinedMethodInspection */
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
            foreach($objects as $object){
                /** @noinspection PhpUndefinedMethodInspection */
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
     * @return array
     */
    public function deleteObject($args = [])
    {
        try {
            if ( ! isset($args['Bucket'])) { return rs(1, '缺少Bucket名称'); }
            if ( ! isset($args['Key'])) { return rs(1, '缺少Key名称'); }
            $this->s3->deleteObject($args)->toArray();
            $exist = $this->existObject([
                'Bucket' => $args['Bucket'],
                'Key' => $args['Key']
            ]);
            if ($exist) {
                return rs(1, '删除失败');
            } else {
                return rs(0, '删除成功');
            }
        } catch (S3Exception $e) {
            $msg = '系统错误';
            $err = $e->getMessage();
            if(mb_strpos($err, 'NoSuchBucket') !== false){
                $msg = '不存在此容器';
            }elseif(mb_strpos($err, 'AccessDenied') !== false){
                $msg = '没有权限删除此对象';
            }
            return rs(1, $msg);
        }
    }

    /**
     * 生成签名下载地址
     *
     * @param array $args = [Bucket, Key, expire(秒)]
     * @param bool  $only_url
     *
     * @return array
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
            $expire = isset($args['expire']) ? intval($args['expire']) : 0;
            if($expire < 1){ $expire = 180; }
            $request = $this->s3->createPresignedRequest($command, "+$expire seconds");
            if($only_url){
                return rs(0, 'ok', ['url'=>(string)$request->getUri()]);
            }else{
                return rs(0, 'ok', ['url_info'=>$request]);
            }
        } catch (\Exception $e) {
            if($only_url){
                return rs(1, '系统错误', ['url'=>'']);
            }else{
                return rs(1, '系统错误', ['url_info'=>[]]);
            }
        }
    }

    /**
     * 得到对象ACL
     *
     * @param array $args = [Bucket, Key]
     *
     * @return mixed
     */
    /*public function getObjectAcl($args = [])
    {
        try {
            if ( ! isset($args['Bucket'])) { return false; }
            if ( ! isset($args['Key'])) { return false; }
            return $this->s3->getObjectAcl($args)->toArray();
        } catch (\Exception $e) {
            return null;
        }
    }*/

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
     * @return array
     */
    public function multipartUpload($args=[], $full=true)
    {
        if ( ! isset($args['Bucket'])) { return rs(1, '缺少Bucket名称'); }
        if ( ! isset($args['Key'])) { return rs(1, '缺少Key名称'); }
        if ( ! isset($args['Source'])) { return rs(1, '缺少Source路径'); }
        $uploader = new MultipartUploader($this->s3, $args['Source'], ['bucket'=>$args['Bucket'], 'key'=>$args['Key']]);
        try {
            set_time_limit(0);
            $result = $uploader->upload();
            $rt = (true === $full) ? $result : $result['ObjectURL'];
            return rs(0, 'ok', ['result'=>$rt]);
        } catch (MultipartUploadException $e) {
            return rs(0, 'ok', ['result'=>$e->getMessage()]);
        }
    }

    /**
     * 启用文件分段上传
     *
     * @param array $args = [
     *                  Bucket = <string>,
     *                  Key = <string>
     *              ]
     * @return mixed
     */
    public function createMultipartUpload($args = [])
    {
        if ( ! isset($args['Bucket'])) { return rs(1, '缺少Bucket名称'); }
        if ( ! isset($args['Key'])) { return rs(1, '缺少Key名称'); }
        $result = $this->s3->createMultipartUpload([
            'Bucket' => $args['Bucket'],
            'Key' => $args['Key']
        ])->toArray();
        return rs(0, 'ok', $result);
    }

    /**
     * 分段上传 上传文件块
     *
     * @param array $args = [
     *                  Bucket = <string>,
     *                  Key = <string>,
     *                  PartNumber = <int>,
     *                  Body = <string || resource || Psr\Http\Message\StreamInterface>,
     *                  UploadId = <string>
     *              ]
     * @return mixed
     */
    public function uploadPart($args = [])
    {
        if ( ! isset($args['Bucket'])) { return rs(1, '缺少Bucket名称'); }
        if ( ! isset($args['Key'])) { return rs(1, '缺少Key名称'); }
        if ( ! isset($args['PartNumber'])) { return rs(1, '缺少PartNumber名称'); }
        if ( ! isset($args['Body'])) { return rs(1, '缺少Body名称'); }
        if ( ! isset($args['UploadId'])) { return rs(1, '缺少UploadId名称'); }
        $result = $this->s3->uploadPart([
            'Bucket' => $args['Bucket'],
            'Key' => $args['Key'],
            'PartNumber' => $args['PartNumber'],
            'Body' => $args['Body'],
            'UploadId' => $args['UploadId']
        ])->toArray();
        return rs(0, 'ok', $result);
    }

    /**
     * 完成所有文件块上传后 合并这些文件
     *
     * @param array $args = [
     *                  Bucket = <string>,
     *                  Key = <string>,
     *                  UploadId = <string>,
     *                  MultipartUpload = <array>
     *              ]
     * @return mixed
     */
    public function completeMultipartUpload($args = [])
    {
        if ( ! isset($args['Bucket'])) { return rs(1, '缺少Bucket名称'); }
        if ( ! isset($args['Key'])) { return rs(1, '缺少Key名称'); }
        if ( ! isset($args['UploadId'])) { return rs(1, '缺少UploadId名称'); }
        if ( ! isset($args['MultipartUpload'])) { return rs(1, '缺少MultipartUpload名称'); }
        $result = $this->s3->completeMultipartUpload([
            'Bucket' => $args['Bucket'],
            'Key' => $args['Key'],
            'UploadId' => $args['UploadId'],
            'MultipartUpload' => $args['MultipartUpload']
        ])->toArray();
        return rs(0, 'ok', $result);
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
            if( ! isset($args['DestinationBucket'])){ return rs(1, '缺少目标Bucket名称'); }
            if( ! isset($args['DestinationKey'])){ return rs(1, '缺少目标Key名称'); }
            if( ! isset($args['CopySource'])){ return rs(1, '缺少CopySource'); }
            $this->s3->copyObject([
                'Bucket' => $args['DestinationBucket'],
                'Key' => $args['DestinationKey'],
                'CopySource' => $args['CopySource'],
            ]);
            $rs = $this->existObject(['Bucket'=>$args['DestinationBucket'], 'Key'=>$args['DestinationKey']]);
            if($rs){
                return rs(0, 'ok');
            }else{
                return rs(1, '复制失败');
            }
        } catch (\Exception $e) {
            return rs(1, '复制失败');
        }
    }
}
