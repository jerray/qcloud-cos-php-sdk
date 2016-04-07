qcloud-cos-php-sdk
===========

腾讯云对象存储服务 PHP SDK

使用方法
--------

### 创建客户端实例

创建SDK客户端实例。`$options`中的参数请到腾讯云对象存储的[密钥管理](https://console.qcloud.com/cos/project)页面获取。

```php
$options = [
    'appId' => 'Your app id',
    'secretId' => 'Your secret id',
    'secretKey' => 'Your secret key',
];
$cos = new jerray\QCloudCos\QCloudCos($options);
```

### 文件操作

接口返回结果详情参见腾讯云对象存储服务文档 [文件操作](http://www.qcloud.com/doc/product/227/%E6%96%87%E4%BB%B6%E6%93%8D%E4%BD%9C)

上传文件

```php
$localFilePath = '/path/to/a/local/file';
$bucketName = 'bucket';
$cosFilePath = '/remote/file/path';
$bizAttr = 'File attributes';

try {
    $response = $cos->uploadFile($localFilePath, $bucketName, $cosFilePath, $bizAttr);
    $code = $response->code;
    $fileUrl = $response->data->access_url;
} catch (jerray\QCloudCos\Exceptions\ClientException $e) {
    $response = $e->getBody();
    $httpMessage = $e->getMessage();
    $httpCode = $e->getCode();
} catch (Exception $e) {
    // ...
}
```

查询文件

```php
$response = $cos->uploadFile($bucketName, $cosFilePath);
```

更新文件bizAttr

```php
$response = $cos->updateFile($bucketName, $cosFilePath, $bizAttr);
```

删除文件

```php
$response = $cos->deleteFile($bucketName, $cosFilePath);
```

下载文件

```
$result = $cos->downloadFile($bucketName, $cosFilePath, $localFilePath);
```

### 目录操作

接口返回结果详情参见腾讯云对象存储服务文档 [目录操作](http://www.qcloud.com/doc/product/227/%E7%9B%AE%E5%BD%95%E6%93%8D%E4%BD%9C)

暂未实现

License
--------------

MIT
