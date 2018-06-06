# Ceph PHP 客户端

**SDK 详细文档：**
[https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html](https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html)

**SDK 案例代码：**
[https://github.com/awsdocs/aws-doc-sdk-examples/tree/master/php/example_code/s3](https://github.com/awsdocs/aws-doc-sdk-examples/tree/master/php/example_code/s3)

## 安装
<pre>
composer require majorbio/ceph
</pre>

## 使用
<pre>
use majorbio\Ceph;
$config = [
    'endpoint' => 'http://s3.xxxx.com:80',
    'access_key' => 'access_key',
    'secret_key' => 'secret_key',
];
$ceph = new Ceph($config);
</pre>

### Bucket操作
<pre>
$ceph->s3->existBucket($args);
$ceph->s3->createBucket($args);
$ceph->s3->listBuckets($args);
$ceph->s3->listBucketsNames($args);
$ceph->s3->deleteBucket($args);
$ceph->s3->getBucketsAcl($args);
</pre>

### Object操作
<pre>
$ceph->s3->existObject($args);
$ceph->s3->createObject($args);
$ceph->s3->listObjects($args);
$ceph->s3->listObjectsNames($args);
$ceph->s3->deleteObject($args);
$ceph->s3->createPresignedRequest($args); //临时URL
</pre>

> 参考 https://github.com/liushuangxi/ceph-amazons3-php.git
