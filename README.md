# Ceph PHP 客户端

**SDK 详细文档：**
[https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html](https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html)

**SDK 案例代码：**
[https://github.com/awsdocs/aws-doc-sdk-examples/tree/master/php/example_code/s3](https://github.com/awsdocs/aws-doc-sdk-examples/tree/master/php/example_code/s3)

## 安装
```
composer require majorbio/ceph
```

## 使用
```
use majorbio\Ceph;
$config = [
    'endpoint' => 'http://s3.xxxx.com:80',
    'access_key' => 'access_key',
    'secret_key' => 'secret_key',
];
$ceph = new Ceph($config);
```

### Bucket操作
```
$ceph->s3->existBucket(['Bucket'=>'my-bucket-1']);
$ceph->s3->createBucket(['Bucket'=>'my-bucket-1']);
$ceph->s3->listBuckets();
$ceph->s3->listBucketsNames();
$ceph->s3->deleteBucket(['Bucket'=>'my-bucket-1']);
$ceph->s3->getBucketsAcl(['Bucket'=>'my-bucket-1']);
```

### Object操作
```
$ceph->s3->existObject(['Bucket'=>'my-bucket-1', 'Key'=>'my-obj-1']);
//上传对象 Body = <string> 字符串类型
$ceph->s3->createObject(['Bucket'=>'my-bucket-1', 'Key'=>'my-obj-1', 'Body'=>'hello world']);
//上传对象 Body = fopen('/路径/x') 资源类型
$ceph->s3->createObject(['Bucket'=>'my-bucket-1', 'Key'=>'my-obj-1', 'Body'=>fopen('C:\Users\admin\Desktop\adobe.photoshop.cs3.rar', 'r')]);
//上传对象 Source = '/路径/x'
$ceph->s3->multipartUpload(['Bucket'=>'my-bucket-1', 'Key'=>'my-obj-1', 'Source'=>'C:\Users\admin\Desktop\pycharm-community-2017.3.4.exe']);
$ceph->s3->listObjects(['Bucket'=>'my-bucket-1']);
$ceph->s3->listObjectsNames(['Bucket'=>'my-bucket-1']);
$ceph->s3->deleteObject(['Bucket'=>'my-bucket-1', 'Key'=>'my-obj-1']);
$ceph->s3->getObjectAcl(['Bucket'=>'my-bucket-1', 'Key'=>'my-obj-1']);
//临时URL
$ceph->s3->createPresignedRequest(['Bucket'=>'my-bucket-1', 'Key'=>'my-obj-1', 'expire'=>600]);
```

> 参考 https://github.com/liushuangxi/ceph-amazons3-php
