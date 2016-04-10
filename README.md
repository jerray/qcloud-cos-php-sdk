qcloud-cos-php-sdk
===========

腾讯云[对象存储服务 COS](http://www.qcloud.com/wiki/COS%E4%BA%A7%E5%93%81%E4%BB%8B%E7%BB%8D) PHP SDK

[![Latest Stable Version](https://poser.pugx.org/jerray/qcloud-cos-php-sdk/v/stable)](https://packagist.org/packages/jerray/qcloud-cos-php-sdk) [![Total Downloads](https://poser.pugx.org/jerray/qcloud-cos-php-sdk/downloads)](https://packagist.org/packages/jerray/qcloud-cos-php-sdk) [![Latest Unstable Version](https://poser.pugx.org/jerray/qcloud-cos-php-sdk/v/unstable)](https://packagist.org/packages/jerray/qcloud-cos-php-sdk) [![License](https://poser.pugx.org/jerray/qcloud-cos-php-sdk/license)](https://packagist.org/packages/jerray/qcloud-cos-php-sdk) [![Build Status](https://travis-ci.org/jerray/qcloud-cos-php-sdk.svg?branch=master)](https://travis-ci.org/jerray/qcloud-cos-php-sdk) [![Coverage Status](https://coveralls.io/repos/github/jerray/qcloud-cos-php-sdk/badge.svg?branch=master)](https://coveralls.io/github/jerray/qcloud-cos-php-sdk?branch=master)

安装
----

```
composer require jerray/qcloud-cos-php-sdk
```

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

各接口返回结果详情参见腾讯云对象存储服务 [RESTful API文档](http://www.qcloud.com/wiki/RESTful_API%E6%96%87%E6%A1%A3)

### 文件操作

完整上传

```php
$localFilePath = '/path/to/a/local/file';
$bucketName = 'bucket';
$cosFilePath = '/remote/file/path';
$bizAttr = 'File attributes';

try {
    $response = $cos->upload($localFilePath, $bucketName, $cosFilePath, $bizAttr);
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

分片上传（超过一定大小的文件需要使用分片上传）

```php
// response为最后一片的响应，与完整上传结构相同
$response = $cos->uploadSlice($localFilePath, $bucketName, $cosFilePath, $bizAttr);
```

查询文件

```php
$response = $cos->queryFile($bucketName, $cosFilePath);
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

创建目录

```php
$response = $cos->createFolder($bucketName, 'test/');
```

目录列表

```php
$limit = 20; // 每页列表数量
$pattern = 'both'; // 显示所有文件和目录 file - 只显示文件；foler - 只显示目录

// direction参数需要配合context参数使用
// context为空时始终取第一页，第一页返回的数据中会含有context参数
// 将此context值传入再次调用，即取到第二页
// direction用来控制翻页方向，next下一页，prev前一页
$context = '';
$direction = 'next';

// 取到第一页 返回当前context为第一页
$response = $cos->listFolder($bucketName, 'test/', $limit, $pattern, $context, $direction);

// next 向后翻页，取到第二页，返回当前context为第二页
$response = $cos->listFolder($bucketName, 'test/', $limit, $pattern, $response->data->context, 'next');

// prev 向前翻页，取到第一页，返回当前context为第一页
$response = $cos->listFolder($bucketName, 'test/', $limit, $pattern, $response->data->context, 'prev');
```

更新目录bizAttr

```php
$response = $cos->updateFolder($bucketName, 'test/');
```

目录查询

```php
$response = $cos->queryFolder($bucketName, 'test/');
```

删除目录

**注意**：目录不为空时会删除失败并抛出 `jerray\QCloudCos\Exceptions\ClientException` 异常

```php
$response = $cos->deleteFolder($bucketName, 'test/');
```

License
--------------

MIT
