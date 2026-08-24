<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ResolverController extends Controller
{
    protected $ttl_min;

    protected $ttl_max;

    protected $ttl_default;

    public function __construct()
    {
        $this->ttl_min = config('opendiscovery.ttl_min');
        $this->ttl_max = config('opendiscovery.ttl_max');
        $this->ttl_default = config('opendiscovery.ttl_default');
    }

    public function frontpage()
    {
        return redirect('https://github.com/OpenDiscoveryBiz/resolver');
    }

    public function lookup(Request $request)
    {
        $id = $request->query('id');
        $pretty = $request->query('pretty');

        if (empty($id)) {
            return response()->json([
                'error' => 'missing_id',
            ], 400);
        }

        $id = Str::upper($id);
        $id = preg_replace('/[^A-Z0-9]+/', '', $id);

        if (! preg_match('/^[A-Z]{2,2}[A-Z0-9]{1,14}+$/', $id)) {
            return response()->json([
                'error' => 'invalid_id',
            ], 400);
        }

        $redirect_search = [];
        for ($i = strlen($id) - 1; $i > 1; $i--) {
            $prefix = substr($id, 0, $i);
            $redirect_search[] = $prefix.'_redirect';
        }

        $id_search = array_merge([
            $id.'_voluntary',
            $id.'_official',
        ], $redirect_search);

        $cached = Cache::many($id_search);

        if ($cached[$id.'_official'] !== null) {
            $official = $cached[$id.'_official'];
        } else {
            $providers = explode(',', config('opendiscovery.provider_root'));
            foreach ($redirect_search as $search) {
                if ($cached[$search] === null) {
                    continue;
                }

                $providers = $cached[$search]['providers'];
            }

            try {
                $official = null;

                for ($redirect_count = 0; $redirect_count < 5; $redirect_count++) {
                    $data = null;
                    $error_message = null;
                    for ($provider_count = 0; $provider_count < 2; $provider_count++) {
                        if (! isset($providers[$provider_count])) {
                            break;
                        }
                        try {
                            $data = $this->fetchData($providers[$provider_count], $id);
                            break;
                        } catch (\Exception $e) {
                            $data = null;
                            $error_message = $e->getMessage();
                        }
                    }

                    if (empty($data)) {
                        throw new \Exception('Official providers down: '.$error_message);
                    }

                    $type = $data['type'] ?? null;
                    if ($type === 'official') {
                        $official = $data;
                        Cache::put($id.'_official', $official, $this->getExpireTime($data['ttl'] ?? 0));
                        break;
                    }
                    if ($type !== 'redirect') {
                        throw new \Exception('Got unsupported type from official providers');
                    }

                    if (empty($data['providers']) || ! is_array($data['providers']) || count($data['providers']) < 1) {
                        throw new \Exception('No providers in redirect from offical provider');
                    }

                    if (! empty($data['id'])) {
                        $cache_key = $data['id'].'_'.$type;
                        if ($cached[$cache_key] === null) {
                            Cache::put($cache_key, $data, $this->getExpireTime($data['ttl'] ?? 0));
                            $cached[$cache_key] = $data;
                        }
                    }

                    $providers = $data['providers'];
                }
            } catch (\Exception $e) {
                $official = [
                    'type' => 'official',
                    'error' => 'upstream_down',
                    'error_detailed' => $e->getMessage(),
                ];
            }
        }

        if (empty($official['error']) && ! empty($official['voluntaryProviders'])) {
            if ($cached[$id.'_voluntary'] !== null) {
                $voluntary = $cached[$id.'_voluntary'];
            } else {
                try {
                    $voluntary = null;

                    $providers = $official['voluntaryProviders'];

                    for ($redirect_count = 0; $redirect_count < 5; $redirect_count++) {
                        $data = null;
                        for ($provider_count = 0; $provider_count < 2; $provider_count++) {
                            if (! isset($providers[$provider_count])) {
                                break;
                            }
                            try {
                                $data = $this->fetchData($providers[$provider_count], $id);
                                break;
                            } catch (\Exception $e) {
                                $data = null;
                            }
                        }

                        if (empty($data)) {
                            throw new \Exception('voluntary providers down');
                        }

                        $type = $data['type'] ?? null;
                        if ($type === 'voluntary') {
                            $voluntary = $data;
                            break;
                        }
                        if ($type !== 'redirect') {
                            throw new \Exception('Got unsupported type from voluntary providers');
                        }

                        if (empty($data['providers']) || ! is_array($data['providers']) || count($data['providers']) < 1) {
                            throw new \Exception('No providers in redirect from voluntary provider');
                        }

                        $providers = $data['providers'];
                    }
                } catch (\Exception $e) {
                    $voluntary = [
                        'type' => 'voluntary',
                        'error' => 'upstream_down',
                        'error_detailed' => $e->getMessage(),
                    ];
                }

                Cache::put($id.'_voluntary', $voluntary, $this->getExpireTime($voluntary['ttl'] ?? 0));
            }
        } else {
            $voluntary = [
                'type' => 'voluntary',
                'error' => 'official_not_available',
            ];
        }

        $ttl = 3600;

        $company = [
            'id' => $id,
            'ttl' => $ttl,
            'official' => $official,
            'voluntary' => $voluntary,
        ];

        if (! empty($pretty)) {
            return response()->json($company, 200, [], JSON_PRETTY_PRINT);
        }

        return response()->json($company, 200);
    }

    public function getExpireTime($ttl)
    {
        $ttl = (int) $ttl;

        if ($ttl < 1) {
            $valid_ttl = $this->ttl_default;
        } else {
            $valid_ttl = max(min($ttl, $this->ttl_max), $this->ttl_min);
        }

        return Carbon::now()->addSeconds($valid_ttl);
    }

    protected function fetchData($host, $id)
    {
        $host = rtrim($host, '/');
        $url = $host.'/.well-known/opendiscovery/'.urlencode($id).'.json';

        $response = Http::timeout(5)
            ->withHeaders([
                'User-Agent' => 'OpenDiscoveryResolver (+https://www.opendiscovery.biz/)',
            ])
            ->withOptions(['allow_redirects' => false])
            ->get($url);

        if ($response->failed()) {
            if ($response->status() < 400 || $response->status() > 499) {
                $response->throw();
            }
        }

        $json = $response->json();

        if (! empty($json['error'])) {
            return $json;
        }

        if (empty($json['id'])) {
            throw new \Exception('Missing ID');
        }

        if (strncmp($id, Str::upper($json['id']), strlen($json['id'])) !== 0) {
            throw new \Exception('Unknown ID returned');
        }

        return $json;
    }
}
