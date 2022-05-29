<?php
declare(strict_types=1);

namespace LessAbstractService\Token;

use JsonException;
use LessValueObject\Composite\ForeignReference;
use LessValueObject\Number\Int\Date\Timestamp;
use LessValueObject\String\Format\Resource\Identifier;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class JwTokenService implements TokenService
{
    public function __construct(
        private readonly string $keyIdentifier,
        private readonly string $keyFile,
    ) {}

    /**
     * @throws JsonException
     */
    public function tokenize(Identifier $id, ForeignReference $subject, Timestamp $expire, ?ServerRequestInterface $request = null): string
    {
        $claims = [
            'jti' => $id,
            'sub' => (string)$subject,
            'iss' => 'less.identity',
            'exp' => $expire,
            'nbf' => time(),
            'iat' => time(),
        ];

        if ($request instanceof ServerRequestInterface) {
            $userAgent = $request->getHeaderLine('User-Agent');
            $params = $request->getServerParams();
            $ip = isset($params['REMOTE_ADDR']) && is_string($params['REMOTE_ADDR'])
                ? $params['REMOTE_ADDR']
                : '';

            $claims['req'] = md5("{$ip}://{$userAgent}");
        }

        return $this->make($claims);
    }

    /**
     * @param array<mixed> $claims
     *
     * @throws JsonException
     */
    private function make(array $claims): string
    {
        $partial = "{$this->encode($this->getHeader())}.{$this->encode($claims)}";

        $key = file_get_contents($this->keyFile);
        assert(is_string($key));
        $key = trim($key);

        if (!openssl_sign($partial, $signature, $key, 'sha512')) {
            throw new RuntimeException();
        }

        return "{$partial}.{$this->encode($signature)}";
    }

    /**
     * @param mixed $input
     *
     * @throws JsonException
     */
    private function encode(mixed $input): string
    {
        if (!is_string($input)) {
            $input = json_encode($input, JSON_THROW_ON_ERROR);
        }

        return str_replace(
            ['+', '/', '='],
            ['-', '_', ''],
            base64_encode($input),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function getHeader(): array
    {
        return [
            'kid' => $this->keyIdentifier,
            'alg' => 'RS512',
        ];
    }
}
