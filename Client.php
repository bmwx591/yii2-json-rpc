<?
namespace nizsheanez\JsonRpc;

use nizsheanez\JsonRpc\Exception;

/**
 * @author sergey.yusupov, alex.sharov
 */
abstract class Client {

    protected $url;

    public function __construct($url = null)
    {
        $this->url = $url;
    }

    public function __call($name, $arguments)
    {
        $id = md5(microtime());
        $request = array(
            'jsonrpc' => '2.0',
            'method'  => $name,
            'params'  => $arguments,
            'id'      => $id
        );

        $jsonRequest = json_encode($request);

        $ctx = stream_context_create(array(
            'http' => array(
                'method'  => 'POST',
                'header'  => "Content-Type: application/json-rpc\r\n",
                'content' => $jsonRequest
            )
        ));

        $jsonResponse = '';
        try {
            $fp = fopen($this->url, 'r', false, $ctx);
            while ($line = fgets($fp))
            {
                $jsonResponse .= trim($line) . "\n";
            }
            fclose($fp);
        } catch (Exception $e) {
            if (isset($fp) && $fp !== false) {
                fclose($fp);
                throw $e;
            }
        }

        if ($jsonResponse === '')
            throw new Exception('fopen failed', Exception::INTERNAL_ERROR);

        $response = json_decode($jsonResponse);

        if ($response === null)
            throw new Exception('JSON cannot be decoded', Exception::INTERNAL_ERROR);

        if ($response->id != $id)
            throw new Exception('Mismatched JSON-RPC IDs', Exception::INTERNAL_ERROR);

        if (property_exists($response, 'error'))
            throw new Exception($response->error->message, $response->error->code);
        else if (property_exists($response, 'result'))
            return $response->result;
        else
            throw new Exception('Invalid JSON-RPC response', Exception::INTERNAL_ERROR);
    }
}
