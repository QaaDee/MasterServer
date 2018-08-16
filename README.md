# Master Server 
This class was created to make a Master Server for the game engine to GoldSrc and Source.

## Prerequisites
* PHP version (7.1 or newer)
* Lib php_sockets (4.3 or newer)
* Server must allow UDP connections

## Installation
The Master Server can be installed using Composer by running the following command:

```sh
composer require qade/master-server
```

## Functions

<table>
	<tr>
		<td>create(string $address, int $port = 27010)</td>
		<td>Create server</td>
	</tr>
	<tr>
		<td>close()</td>
		<td>Close server</td>
	</tr>
	<tr>
		<td>listen()</td>
		<td>Accepts the request from the client and processes it</td>
	</tr>
	<tr>
		<td>addServer(string $server)</td>
		<td>Add a server to the server list</td>
	</tr>
	<tr>
		<td>addServers(array $servers)</td>
		<td>Adds a servers to the server list</td>
	</tr>
	<tr>
		<td>clearServers()</td>
		<td>Cleaning the server list</td>
	</tr>
	<tr>
    	<td>removeServer(string $server)</td>
    	<td>Remove a server to the server list</td>
    </tr>
</table>




