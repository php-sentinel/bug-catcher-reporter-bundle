<?php
/**
 * Created by PhpStorm.
 * User: Jozef Môstka
 * Date: 23. 5. 2024
 * Time: 10:15
 */
namespace BugCatcher\Reporter\Writer;

use BugCatcher\Reporter\UrlCatcher\UriCatcherInterface;
use Exception;
use Monolog\LogRecord;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HttpWriter implements WriterInterface {


	public function __construct(
		private readonly HttpClientInterface $client,
		private readonly UriCatcherInterface $uriCatcher,
		private readonly string              $project,
		private readonly string              $minLevel,
	) {}

	function write(LogRecord $record): void {
		if ($record->level->value < $this->minLevel) {
			return;
		}
		$data = [
			"message"     => $record->message,
			"level"       => $record->level->value,
			"projectCode" => $this->project,
			"requestUri"  => $this->uriCatcher->getUri(),
		];
		$response = $this->client->request("POST", "/api/log_records", [
			'headers' => [
				'Content-Type' => 'application/json',
				'accept'       => 'application/json',
			],
			"body"    => json_encode($data),
		]);
		if ($response->getStatusCode() !== 201) {
			throw new Exception("Error during sending log record to BugCatcher.\n" . $response->getContent(false));
		}
	}
}