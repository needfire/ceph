<?php
namespace majorbio;

use Aws\S3\S3Client;

//https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html

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
     * https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#createbucket
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
     * https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#deletebucket
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
     * https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#headbucket
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
     * https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#listbuckets
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
     * 容器列表只包含容器名称
     *
     * https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#listbuckets
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
     * https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#listbuckets
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
     * 追加一个用户到acl
     *
     * https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#putbucketacl
     *
     * @param array $args = [
     *                  Bucket = 'xxx',
     *                  id = '',
     *                  acl = 'private|public-read|public-read-write|authenticated-read',
     *                  permission = 'FULL_CONTROL|WRITE|WRITE_ACP|READ|READ_ACP',
     *                  Type = 'CanonicalUser|AmazonCustomerByEmail|Group'
     *              ]
     *
     * @return mixed
     */
    public function appendBucketAcl($args = [])
    {
        if ( ! isset($args['Bucket'])) { return false; }
        if ( ! isset($args['ID'])) { return false; }
        if ( ! isset($args['ACL'])) { $args['ACL'] = 'public-read-write'; }
        if ( ! isset($args['Permission'])) { $args['Permission'] = 'FULL_CONTROL'; }
        if ( ! isset($args['Type'])) { $args['Type'] = 'CanonicalUser'; }
        //先取出容器的ACL
        if ( ! $this->existBucket(['Bucket'=>$args['Bucket']])) { return false; }
        $old_acl = $this->getBucketAcl(['Bucket'=>$args['Bucket']]);
        $Grants = [];
        foreach($old_acl['Grants'] as $g) {
            $g['Grantee']['Type'] = 'CanonicalUser';
            $Grants[] = $g;
        }
        $Grants[] = [
            'Grantee'=>[
                'ID' => $args['ID'],
                'Type' => $args['Type'],
            ],
            'Permission'=>$args['Permission']
        ];
        $params = [
            'Bucket' => $args['Bucket'],
            'ACL' => $args['ACL'],
            'Grants' => $Grants,
            'Owner' => $old_acl['Owner'],
        ];
        /*$params = array(
            'ACL' => $args['ACL'],
            'Grants' => array(
                array(
                    'Grantee' => array(
                        'ID' => $args['ID'],
                        'Type' => 'CanonicalUser',
                    ),
                    'Permission' => 'FULL_CONTROL',
                ),
                // ... repeated
            ),
            'Owner' => $old_acl['Owner'],
            // Bucket is required
            'Bucket' => $args['Bucket'],
        );*/
        //ddd($params,1,1);
        $result = $this->s3->putBucketAcl($params)->toArray();
        /*$result = $this->s3->putBucketAcl([
            'Bucket' => $args['Bucket'],
            'GrantFullControl' => 'id=guofeng.liu',
        ]);*/
        ddd($result,0,1);
    }
}
