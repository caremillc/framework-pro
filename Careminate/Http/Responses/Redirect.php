<?php  declare(strict_types=1);
namespace Careminate\Http\Responses;

class Redirect extends Response
{
    public function __construct(string $url)
    {
        parent::__construct('', 302, ['Location' => $url]);
    }

    public function send(): void
    {
        $url = $this->getHeader('Location');
        if ($url === null) {
            throw new \RuntimeException('No location header set for redirect.');
        }

        header('Location: ' . $url, true, $this->getStatus());
        exit;
    }
}