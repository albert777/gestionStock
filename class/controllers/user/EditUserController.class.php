<?php
/**
 * Created by PhpStorm.
 * User: Unknown
 * Date: 01/07/2017
 * Time: 17:02
 */

namespace gestionStock\controllers\user;

use gestionStock\DAO\user\MysqlUserDao;
use gestionStock\exceptions\user\InvalidActionException;
use gestionStock\exceptions\user\InvalidDataException;
use gestionStock\utils\ErrorMessageManager;
use gestionStock\utils\MysqlConnection;
use gestionStock\views\user\EditUserView;
use gestionStock\views\user\HomeView;
use gestionStock\controllers\IController;

class EditUserController extends AlterUserController implements IController
{
 public function doAction()
    {
        $data = array();
        $isTransactioStarted = false;
        $pdo = null;


        try {
            if (!isset($_GET["id"]))
                throw new InvalidActionException("Id manquant");

            $id = (int)$_GET["id"];


            $pdo = MysqlConnection::getConnection();
            $stockDao = new MysqlUserDao($pdo);


            $stock = $stockDao->findById($id);

            if ($stock === null)
                throw new InvalidActionException("Impossible de retrouver la pièce avec son id" . $id);

            $data['stock'] = $stock;

            if (!isset($_POST['id']))
            {
                $view = new EditUserView();
                $view->showView($data);
                return;
            }


            //On a soumis le formulaire
            $invalidFields = $this->validPostedDataAndSet($stock);


            if (count($invalidFields) > 0)
                throw new InvalidDataException("Données soumises invalides", $invalidFields);

            else
            {
                $isTransactioStarted = $pdo->beginTransaction();
                $stockDao->insertOrUpdate($stock);
                $pdo->commit();

                header("Location: " . $_SERVER["REQUEST_SCHEME"] . '://' . $_SERVER["HTTP_HOST"]);
            }

        }
        catch (\Exception $ex) {
            if ($ex instanceof InvalidActionException) {
                ErrorMessageManager::getInstance()->addMessage($ex->getMessage());
                header("Location: index.php");
                return;
            }

            if ($ex instanceof \PDOException && $ex->getCode() == 23000) {
                $data['error'] = "The email already exists";
                $data['invalidFields'] = array("email");
            } else
                $data['error'] = $ex->getMessage();

            if ($ex instanceof InvalidDataException)
                $data['invalidFields'] = $ex->getInvalidData();

            if ($isTransactioStarted)
                $pdo->rollBack();


            $view = new EditUserView();
            $view->showView($data);


        }
    }
}

