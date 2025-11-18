<?php

class MonitorRamais
{
    private string $arquivoRamais;
    private string $arquivoFilas;
    private array $statusRamais = [];
    private array $infoRamais = [];

    public function __construct(string $arquivoRamais, string $arquivoFilas)
    {
        $this->arquivoRamais = $arquivoRamais;
        $this->arquivoFilas = $arquivoFilas;
    }

    public function processar(): array
    {
        $this->lerStatusFilas();
        $this->lerInfoRamais();
        return $this->infoRamais;
    }

    private function lerStatusFilas(): void
    {
        $filas = file($this->arquivoFilas);

        foreach ($filas as $linha) {
            if (strstr($linha, 'SIP/')) {

                if (strstr($linha, '(Ring)')) {
                    $this->definirStatus($linha, 'chamando');
                }

                if (strstr($linha, '(In use)')) {
                    $this->definirStatus($linha, 'ocupado');
                }

                if (strstr($linha, '(Not in use)')) {
                    $this->definirStatus($linha, 'disponivel');
                }
            }
        }
    }

    private function definirStatus(string $linha, string $status): void
    {
        $partes = explode(' ', trim($linha));
        list($tech, $ramal) = explode('/', $partes[0]);
        $this->statusRamais[$ramal] = ['status' => $status];
    }

    private function lerInfoRamais(): void
    {
        $ramais = file($this->arquivoRamais);

        foreach ($ramais as $linha) {
            $arr = array_values(array_filter(explode(' ', $linha)));

            // offline
            if (trim($arr[1]) == '(Unspecified)' && trim($arr[4]) == 'UNKNOWN') {
                list($name, $username) = explode('/', $arr[0]);
                $this->infoRamais[$name] = [
                    'nome'   => $name,
                    'ramal'  => $username,
                    'online' => false,
                    'status' => $this->statusRamais[$name]['status'] ?? null
                ];
            }

            // online
            if (isset($arr[5]) && trim($arr[5]) == "OK") {
                list($name, $username) = explode('/', $arr[0]);
                $this->infoRamais[$name] = [
                    'nome'   => $name,
                    'ramal'  => $username,
                    'online' => true,
                    'status' => $this->statusRamais[$name]['status'] ?? null
                ];
            }
        }
    }
}

header("Content-type: application/json; charset=utf-8");

$monitor = new MonitorRamais('ramais', 'filas');
echo json_encode($monitor->processar());

?>