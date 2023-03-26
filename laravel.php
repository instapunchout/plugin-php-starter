<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;


// can be tested using:
// http://localhost:8081/index.php/punchout?id=LtwJL8Ss651RLvTdkgMqLL

Route::get('/punchout', function () {

    if (isset($_GET['action'])) {
        $action = $_GET['action'];

        if ($action == 'order.json') {

            $token = $_GET['token'];
            $res = Http::post('https://punchout.cloud/authorize', ["authorization" => $token])->json();
            if ($res["authorized"] != true) {
                echo json_encode(["error" => "You're not authorized"]);
                exit;
            }
            $new_order = json_decode(file_get_contents('php://input'), true);
            $user = instapunchout_prepare_user($new_order);
            $order = instapunchout_create_order($user, $new_order);
            echo json_encode($order);
            exit;

        } else if ($action == 'script') {
            $punchout_id = $_SESSION['punchout_id'];
            if (isset($punchout_id) && isset($user)) {
                echo Http::get('https://punchout.cloud/punchout.js?id=' . $punchout_id)->body();
            }
            exit;

        } else if ($action == 'message') {
            $punchout_id = $_SESSION['punchout_id'];

            header('Content-Type: application/json');

            if (isset($punchout_id)) {

                $cart = todo("get cart json, should include currency, line items with each"
                    . "name, price, sku, quantity and variant_id needed later to create order");

                $data = [
                    'cart' => [
                        'Laravel1' => $cart,
                    ]
                ];
                $response = Http::post('https://punchout.cloud/cart/' . $punchout_id, $data)->json();
            } else {
                $response = ['message' => "You're not in a punchout session"];
            }
            echo json_encode($response);
            exit;
        } else {
            echo "unknown action";
            exit;
        }

    }

    // no need for further sanization as we need to capture all the server data as is
    $server = json_decode(json_encode($_SERVER), true);
    // no need for further sanization as we need to capture all the query data as is
    $query = json_decode(json_encode($_GET), true);

    $data = array(
        'headers' => getallheaders(),
        'server' => $server,
        'body' => file_get_contents('php://input'),
        'query' => $query,
    );

    $res = Http::post('https://punchout.cloud/proxy', $data)->json();

    if ($res['action'] == 'login') {

        // use customer data object to trigger login event
        $user = instapunchout_prepare_user($res);

        // set user as logged in
        todo("set user as logged in");

        // save the punchout_id in the logged in session
        $_SESSION['punchout_id'] = $res['punchout_id'];

        // clear user cart
        todo("clear user cart");

        // redirect to home page
        header('Location: /');
    }
    exit;


});

function todo($todo)
{
    echo "TODO: " . $todo;
    exit;
    return null;
}
function instapunchout_prepare_user($data)
{
    $email = $data['email'];
    $user = todo("find user by email");

    if (!isset($user)) {
        $data['firstname'];
        $data['lastname'];
        $user = todo("create new user");
    }

    if (isset($data['properties'])) {
        foreach ($data['properties'] as $field => $value) {
            $user[$field] = $value;
        }
        todo("update user with the updated fields");
    }

    return $user;
}

function instapunchout_create_order($user,$new_order) {
    todo("create order for the provided user");
}
