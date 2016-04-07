qcloud-cos-php-sdk
===========

腾讯云对象存储服务 PHP SDK

[![Latest Stable Version](https://poser.pugx.org/jerray/qcloud-cos-php-sdk/v/stable)](https://packagist.org/packages/jerray/qcloud-cos-php-sdk) [![Total Downloads](https://poser.pugx.org/jerray/qcloud-cos-php-sdk/downloads)](https://packagist.org/packages/jerray/qcloud-cos-php-sdk) [![Latest Unstable Version](https://poser.pugx.org/jerray/qcloud-cos-php-sdk/v/unstable)](https://packagist.org/packages/jerray/qcloud-cos-php-sdk) [![License](https://poser.pugx.org/jerray/qcloud-cos-php-sdk/license)](https://packagist.org/packages/jerray/qcloud-cos-php-sdk) [![Build Status](https://travis-ci.org/jerray/qcloud-cos-php-sdk.svg?branch=master)](https://travis-ci.org/jerray/qcloud-cos-php-sdk)

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

### 文件操作

接口返回结果详情参见腾讯云对象存储服务文档 [文件操作](http://www.qcloud.com/doc/product/227/%E6%96%87%E4%BB%B6%E6%93%8D%E4%BD%9C)

上传文件

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

接口返回结果详情参见腾讯云对象存储服务文档 [目录操作](http://www.qcloud.com/doc/product/227/%E7%9B%AE%E5%BD%95%E6%93%8D%E4%BD%9C)

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
