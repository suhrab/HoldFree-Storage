<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Anton
 * Date: 7/26/13
 * Time: 12:44 PM
 * To change this template use File | Settings | File Templates.
 */

use Phalcon\DI\FactoryDefault,
    Phalcon\Mvc\Micro,
    Phalcon\Config\Adapter\Ini as IniConfig,
    Phalcon\Db\Adapter\Pdo\Mysql as MysqlAdapter;

set_error_handler(function($errno , $errstr ,$errfile, $errline, $errcontext ){
        throw new ErrorException($errstr, $errno, E_ALL | E_STRICT, $errfile, $errline);
    }, $error_types = E_ALL | E_STRICT);

require_once 'config.php';
require_once 'functions.php';

set_exception_handler(function(\Exception $e){
        echo json_encode(['error' => $e->getMessage()]);
});

$loader = new \Phalcon\Loader();

$loader->registerDirs(array(
    __DIR__ . '/models/'
))->register();

$di = new \Phalcon\DI\FactoryDefault();

//Set up the database service
$di->set('db', function(){
    return new MysqlAdapter(array(
        "host" => DB_PORT == '' ? DB_HOST : DB_HOST . ':' . DB_PORT,
        "username" => DB_USER,
        "password" => DB_PASS,
        "dbname" => DB_NAME
    ));
});

$app = new \Phalcon\Mvc\Micro($di);

$app->notFound(function() use ($app){
    $app->response->setStatusCode(404, null)->sendHeaders();
    echo 'Unknown api call';
});

//define the routes here
$app->post('/api/uploadFile', function() use ($app) {
        $return_json = array(
            'success' => false,
            'message' => ''
        );

        $uploaddir = DIR_UPLOAD . '/' . date('Y-m-d');
        if(!is_dir($uploaddir))
            mkdir($uploaddir);

        $filePath = tempnam($uploaddir, '');

        if (move_uploaded_file($_FILES['filename']['tmp_name'], $filePath)) {
            do{
                $uuid = gen_uuid();

                $file = Files::findFirst(array(
                        'conditions' => sprintf('uuid = "%s"', $uuid)
                    ));
            } while($file);
            $file = new Files();
            $file->uuid = $uuid;
            $file->absolute_filepath = $filePath;
            $file->created = date('Y-m-d H:i:s');
            $file->filesize = filesize($filePath);
            $file->save();

            $return_json['success'] = true;
            $return_json['fileURL'] = SERVER_URL . '/getFile/' . $uuid;
        } else {
            $return_json['success'] = false;
            $return_json['message'] = 'Possible file upload attack!';
        }

        echo json_encode($return_json);
});

$app->get('/api/deleteFile/{uuid}', function($file_uuid) use ($app) {
    if(empty($file_uuid))
        throw new ErrorException('Файл не найден');

    $deleteSql = <<<SQL
DELETE FROM Files WHERE uuid = :uuid
SQL;
    $deleteStmt = $app->db->prepare($deleteSql);
    $deleteStmt->execute(array(
            'uuid' => $file_uuid
        ));
    if($deleteStmt->rowCount() == 0)
        throw new ErrorException('Файл не найден');

    echo json_encode(
        array(
            'success' => 'Файл удален'
        )
    );
});

$app->get('/api/info', function() use ($app) {
    echo json_encode(
        array(
            'disk_total_space' => disk_total_space(DIR_UPLOAD),
            'disk_free_space' => disk_free_space(DIR_UPLOAD),
            'files_total' => Files::count()
        )
    );
});

$app->get('/getFile/{uuid}', function($file_uuid) use ($app) {
    if(empty($file_uuid))
        throw new ErrorException('Файл не найден');

    $file = Files::findFirst(array(
            'conditions' => sprintf('uuid = "%s"', $file_uuid)
        ));
    if(!$file)
        throw new ErrorException('Файл не найден');

    if(!file_exists($file->absolute_filepath))
        throw new ErrorException('Файл не найден');

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename='.basename($file->absolute_filepath));
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file->filesize));
    ob_clean();
    flush();
    readfile($file->absolute_filepath);
    exit;
});

$app->handle();