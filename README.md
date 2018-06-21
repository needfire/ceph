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
//SourceFile = '/路径/x'
//$rs = $ceph->createObject(['Bucket'=>'lgf-1', 'Key'=>'s3.txt', 'SourceFile'=>'C:\Users\admin\Desktop\s3.txt']);
//上传对象 Body = fopen('/路径/x') 资源类型
$ceph->s3->createObject(['Bucket'=>'my-bucket-1', 'Key'=>'my-obj-1', 'Body'=>fopen('C:\Users\admin\Desktop\adobe.photoshop.cs3.rar', 'r')]);
//上传对象 Source = '/路径/x'
$ceph->s3->multipartUpload(['Bucket'=>'my-bucket-1', 'Key'=>'my-obj-1', 'Source'=>'C:\Users\admin\Desktop\pycharm-community-2017.3.4.exe']);
$ceph->s3->listObjects(['Bucket'=>'my-bucket-1']);
$ceph->s3->listObjectsNames(['Bucket'=>'my-bucket-1']);
//文件、文件夹方式列出
$ceph->s3->listFolderFile(['Bucket'=>'my-bucket-1', 'Dir'=>'my-dir-1'])
$ceph->s3->deleteObject(['Bucket'=>'my-bucket-1', 'Key'=>'my-obj-1']);
$ceph->s3->getObjectAcl(['Bucket'=>'my-bucket-1', 'Key'=>'my-obj-1']);
//临时URL
$ceph->s3->createPresignedRequest(['Bucket'=>'my-bucket-1', 'Key'=>'my-obj-1', 'expire'=>600]);
//复制
$rs = $ceph->copyObject(['DestinationBucket'=>'my-bucket-2', 'DestinationKey'=>'copy-my-obj-1', 'CopySource'=>'/my-bucket-1/my-obj-1']);
```

### 其他操作
```
abort_multipart_upload()                       中止分段上传
can_paginate()								   检查操作是否可以分页
complete_multipart_upload()					   通过汇编以前上传的零件完成分段上传
copy()										   将一个对象从一个S3位置复制到另一个位置
copy_object()								   创建已存储在Amazon S3中的对象的副本。
create_bucket()								   创建一个新的桶
create_multipart_upload()					   启动分段上传并返回上传ID
delete_bucket()								   删除存储桶。必须删除存储区中的所有对象（包括所有对象版本和删除标记），然后才能删除存储区本身。
delete_bucket_analytics_configuration()		   删除存储桶的分析配置（由分析配置ID指定）。
delete_bucket_cors()						   删除为存储桶设置的Cors配置信息
delete_bucket_encryption()					   从存储桶中删除服务器端加密配置。
delete_bucket_inventory_configuration()		   从存储桶中删除清单配置（由清单ID标识）。
delete_bucket_lifecycle()				       从存储桶中删除生命周期配置。
delete_bucket_metrics_configuration()		   从存储桶中删除指标配置（由指标配置ID指定）
delete_bucket_policy()						   从存储桶中删除策略。
delete_bucket_replication()					   从存储桶中删除复制配置。
delete_bucket_tagging()						   从存储桶中删除标签。
delete_bucket_website()						   该操作从存储桶中删除网站配置。
delete_object()								   删除对象的空版本（如果有），并插入一个删除标记，该标记将成为该对象的最新版本。如果不存在空版本，则Amazon S3不会删除任何对象。
delete_object_tagging()						   从现有对象中移除标记集。
delete_objects()							   通过此操作，您可以使用单个HTTP请求从存储桶中删除多个对象。您最多可以指定1000个密钥。
download_file()								   将S3对象下载到文件
download_fileobj()							   从S3下载一个对象到一个类似文件的对象。
generate_presigned_post()				       构建用于预先登记的s3帖子的url和表单字段
generate_presigned_url()					   生成给定客户端，其方法和参数的预先注册的url
get_bucket_accelerate_configuration()		   返回存储桶的加速配置。
get_bucket_acl()							   获取存储桶的访问控制策略。
get_bucket_analytics_configuration()		   获取存储桶的分析配置（由分析配置ID指定）。
get_bucket_cors()							   返回存储桶的cors配置
get_bucket_encryption()						   返回存储桶的服务器端加密配置。
get_bucket_inventory_configuration()           从存储桶返回库存配置（由库存ID标识）。
get_bucket_lifecycle()                         此操作已弃用，可能无法按预期方式运行。这个操作不应该继续使用，而仅仅是为了向后兼容。
get_bucket_lifecycle_configuration()           返回存储桶上设置的生命周期配置信息。
get_bucket_location()                          返回存储区所在的区域。
get_bucket_logging()                           返回存储桶的日志记录状态以及用户必须查看和修改该状态的权限。要使用GET，您必须是存储桶所有者
get_bucket_metrics_configuration()             从存储桶中获取指标配置（由指标配置ID指定）。
get_bucket_notification()                      已弃用，
get_bucket_notification_configuration()        返回存储桶的通知配置。
get_bucket_policy()                            返回指定存储桶的策略。
get_bucket_replication()                       返回存储桶的复制配置。
get_bucket_request_payment()                   返回存储桶的请求付款配置。
get_bucket_tagging()                           返回与存储桶关联的标记集
get_bucket_versioning()                        返回存储桶的版本控制状态
get_bucket_website()                           返回存储桶的网站配置。
get_object()                                   从Amazon S3中检索对象。
get_object_acl()                               返回对象的访问控制列表（ACL）。
get_object_tagging()                           返回对象的标记集。
get_object_torrent()                           从存储桶中返回种子文件。
get_paginator()                                为操作创建分页程序
get_waiter()                                   返回可以等待某种情况的对象
head_bucket()                                  此操作对于确定存储桶是否存在以及您是否有权访问它非常有用。
head_object()								   HEAD操作从对象中检索元数据而不返回对象本身。如果您只对对象的元数据感兴趣，此操作很有用。要使用HEAD，您必须具有对该对象的读取权限
list_bucket_analytics_configurations()         列出存储桶的分析配置。
list_bucket_inventory_configurations()         返回存储桶的清单配置列表
list_bucket_metrics_configurations()           列出存储桶的指标配置
list_buckets()                                 返回请求已通过身份验证的发件人拥有的所有存储桶的列表
list_multipart_uploads()                       此操作列出正在进行的分段上传
list_object_versions()                         返回有关存储桶中所有对象版本的元数据
list_objects()                                 返回存储桶中某些或全部（最多1000个）对象。您可以使用请求参数作为选择条件来返回存储桶中对象的子集。
list_objects_v2()                              返回存储桶中某些或全部（最多1000个）对象。您可以使用请求参数作为选择条件来返回存储桶中对象的子集。注意：ListObjectsV2是经过修改的List Objects API，我们建议您使用此修订的API来进行新的应用程序开发。
list_parts()                                   列出为特定分段上传上传的部分
put_bucket_accelerate_configuration()          设置现有存储桶的加速配置。
put_bucket_acl()                               使用访问控制列表（ACL）设置存储桶的权限
put_bucket_analytics_configuration()           为存储桶设置分析配置（由分析配置ID指定）。
put_bucket_cors()                              设置存储桶的cors配置。
put_bucket_encryption()                        创建新的服务器端加密配置（或者替换现有的加密配置）
put_bucket_inventory_configuration()           从存储桶添加库存配置（由库存ID标识）
put_bucket_lifecycle()                         已弃用
put_bucket_lifecycle_configuration()           为您的存储桶设置生命周期配置。如果存在生命周期配置，则替换它
put_bucket_logging()                           设置存储桶的日志参数并指定谁可以查看和修改日志记录参数的权限。要设置存储桶的日志记录状态，您必须是存储桶拥有者
put_bucket_metrics_configuration()             设置存储桶的度量标准配置（由度量配置标识指定）
put_bucket_notification()                      已弃用
put_bucket_notification_configuration()        为存储桶启用指定事件的通知
put_bucket_policy()                            替换存储桶上的策略。如果存储桶已有策略，则此请求中的策略将完全取代它
put_bucket_replication()                       创建新的复制配置（或者替换现有的配置）
put_bucket_request_payment()                   设置存储桶的请求付款配置。默认情况下，存储桶所有者支付从存储桶下载。此配置参数使存储桶拥有者（仅）能够指定请求下载的人员将被收取下载费用。关于申请人付款的文件可以在
put_bucket_tagging()                           设置存储桶的标签
put_bucket_versioning()                        设置现有存储桶的版本控制状态。要设置版本控制状态，您必须是存储桶所有者
put_bucket_website()                           设置存储桶的网站配置
put_object()								   向桶中添加一个对象。
put_object_acl()                               使用acl子资源为存储桶中已经存在的对象设置访问控制列表（ACL）权限
put_object_tagging()						   将提供的标记集设置为存储桶中已存在的对象
restore_object()                               将对象的归档副本恢复到Amazon S3
select_object_content()                        此操作基于简单的结构化查询语言（SQL）语句过滤Amazon S3对象的内容。在请求中，除了SQL表达式之外，还必须指定对象的数据序列化格式（JSON或CSV）。Amazon S3使用它将对象数据解析为记录，并仅返回与指定SQL表达式匹配的记录。您还必须指定响应的数据序列化格式。
upload_file()                                  将文件上传到S3对象
upload_fileobj() 							   将类似文件的对象上传到S3
upload_part()								   通过分段上传上传零件
upload_part_copy()                             通过从现有对象复制数据作为数据源来上传零件
```

> 参考 https://github.com/liushuangxi/ceph-amazons3-php
