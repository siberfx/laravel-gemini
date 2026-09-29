# Laravel Gemini

A production-ready Laravel package to integrate with the Google Gemini API. Supports text, image, video, audio, long-context, structured output, files, caching, function-calling and understanding capabilities.

[![Tests](https://github.com/siberfx/laravel-gemini/actions/workflows/tests.yml/badge.svg)](https://github.com/siberfx/laravel-gemini/actions/workflows/tests.yml)
[![Version](https://img.shields.io/packagist/v/siberfx/laravel-gemini.svg)](https://packagist.org/packages/siberfx/laravel-gemini)
[![Downloads](https://img.shields.io/packagist/dt/siberfx/laravel-gemini.svg)](https://packagist.org/packages/siberfx/laravel-gemini)
[![License](https://img.shields.io/packagist/l/siberfx/laravel-gemini.svg)](https://packagist.org/packages/siberfx/laravel-gemini)
[![PHP](https://img.shields.io/badge/PHP-8.4%20%7C%208.5-777bb4.svg)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-12%20%7C%2013-ff2d20.svg)](https://github.com/siberfx/laravel-gemini)

## Requirements

- PHP 8.4 or 8.5
- Laravel 12 or 13

## Features

- 🤖 Text generation with context and history
- 🖼️ Image generation and understanding
- 🎥 Video generation and analysis
- 🔊 Audio synthesis and transcription
- 📄 Document processing and understanding
- 🔍 Embeddings generation
- 📊 File management capabilities
- ⚡ Real-time streaming responses
- 🛡️ Configurable safety settings
- 🗄️ Caching for pre-processed content

## Installation

```bash
composer require siberfx/laravel-gemini
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=gemini-config
```

Add your Gemini API key to your `.env` file:

```env
GEMINI_API_KEY=your_gemini_api_key_here
```

## Configuration (detailed)

Configuration lives in `config/gemini.php`. Below are the most important keys and recommended defaults:

| Key | Description | Default |
|---|---:|---|
| `api_key` | Your Gemini API key. | `env('GEMINI_API_KEY')` |
| `base_uri` | Base API endpoint. | `https://generativelanguage.googleapis.com` |
| `default_provider` | Which provider config to use by default. | `gemini` |
| `timeout` | Request timeout in seconds. | `30` |
| `retry_policy.max_retries` | Retry attempts on connection errors, `429` and `5xx`. | `3` |
| `retry_policy.retry_delay` | Delay between retries in ms (`Retry-After` wins on `429`). | `1000` |
| `logging` | Log requests/responses (useful for debugging). | `false` |
| `stream.chunk_size` | Stream buffer chunk size. | `1024` |
| `stream.timeout` | Stream timeout (ms). | `1000` |
| `caching.default_ttl` | Default TTL for cache expiration (e.g., '3600s'). | `'3600s'` |
| `caching.max_page_size` | Default page size for listing caches. | `50` |
| `long_running.poll_interval` | Seconds between polls for `predictLongRunning` jobs. | `5` |
| `long_running.timeout` | Max seconds to wait for a long-running job. | `600` |

All scalar values can be overridden via `.env`: `GEMINI_TIMEOUT`, `GEMINI_MAX_RETRIES`, `GEMINI_RETRY_DELAY`, `GEMINI_CACHE_TTL`, `GEMINI_POLL_INTERVAL`, `GEMINI_POLL_TIMEOUT`, …

### Providers / models / methods

The `providers` array lets you map capability types to models and HTTP methods the provider uses:

| Provider | Capability | Config key | Default model | Default method |
|---|---:|---|---:|---|
| `gemini` | text | `providers.gemini.models.text` | `gemini-2.5-flash-lite` | `generateContent` |
| `gemini` | image | `providers.gemini.models.image` | `gemini-2.5-flash-image-preview` | `generateContent` or `predict` |
| `gemini` | video | `providers.gemini.models.video` | `veo-3.0-fast-generate-001` | `predictLongRunning` |
| `gemini` | audio | `providers.gemini.models.audio` | `gemini-2.5-flash-preview-tts` | `generateContent` |
| `gemini` | embeddings | `providers.gemini.models.embedding` | `gemini-embedding-001` | n/a (embeddings endpoint) |

**Speech config** (`providers.gemini.default_speech_config`) example:

```php
// Fallback voice used for single-speaker TTS when ->voiceName() is not called.
'default_speech_config' => [
    'voiceName' => 'Kore',
],
```

## Dynamic API Key Configuration

By default, Laravel Gemini reads the API key from your `.env` file (`GEMINI_API_KEY`).

However, you can now **set the API key dynamically at runtime** using the new `setApiKey()` method.  
This is useful when you want to switch between multiple keys (e.g. per-user or per-request).

**Example:**

```php
use Siberfx\LaravelGemini\Facades\Gemini;

// Dynamically set API key (takes priority over .env)
Gemini::setApiKey('my-custom-api-key');

// Use Gemini as usual
$response = Gemini::text()
    ->prompt('Hello Gemini!')
    ->generate();

echo $response->content();

// Or chain it
$response = Gemini::setApiKey($user->gemini_key)->text()->prompt('Hi')->generate();
```

> `setApiKey()` changes the key on the shared `Gemini` singleton for the rest of the request / job.

If `setApiKey()` is not called, the package will automatically use the default key from `.env`.

**ApiKey priority order:**

1. Manually set key via `Gemini::setApiKey()`
2. Config value (`config/gemini.php`)
3. `.env` variable (`GEMINI_API_KEY`)

---

## Builder APIs — full method reference

This package exposes a set of builder-style facades: `Gemini::text()`, `Gemini::image()`, `Gemini::video()`, `Gemini::audio()`, `Gemini::embeddings()`, `Gemini::files()` and `Gemini::caches()`.

Below is a concise reference of commonly available chainable methods and what they do. Method availability depends on the builder.

### Common response helpers (Response object)
When you call `->generate()` (or a polling save on long-running jobs) you typically get a response object with these helpers:

- `content()` — main output (text for `TextResponse`, raw bytes for image / audio / video).  
- `model()` — model name used.  
- `usage()` — usage / billing info returned by the provider.  
- `requestId()` — provider request id.  
- `parts()` / `finishReason()` — raw candidate parts and finish reason.  
- `get('dot.path', $default)` — read any value from the raw payload.  
- `toArray()` / `json()` — full payload (responses are also `Arrayable` and `JsonSerializable`).  
- `save($path)` — persist a media result to disk.

`TextResponse` additionally offers `decode()` (parse structured JSON output) and `functionCalls()` (function calls requested by the model).

### Errors

Failed requests throw typed exceptions (all extend `Siberfx\LaravelGemini\Exceptions\BaseException`) carrying the API's error message:

| Status | Exception |
|---|---|
| `400` | `ValidationException` |
| `401` / `403` | `AuthenticationException` |
| `429` | `RateLimitException` (`$e->retryAfter`) |
| `5xx` | `ApiException` |
| connection / other | `NetworkException` |

---

### Gemini::

```php
use Siberfx\LaravelGemini\Facades\Gemini;
```

### TextBuilder (`Gemini::text()`)

Use for: chat-like generation, long-context text, structured output, and multimodal understanding (text responses after uploading files).

Common methods:

| Method | Args | Description |
|---|---:|---|
| `model(string)` | model id | Choose model to use. |
| `prompt(string)` | user prompt | Main prompt. |
| `system(string)` | system instruction | System-level instruction. |
| `history(array)` | chat history | Conversation history array (role/parts structure). |
| `historyFromModel($query, string $bodyColumn, string $roleColumn)` | Eloquent query | Build history from database rows. |
| `structuredSchema(array)` | JSON Schema | Ask model to produce structured JSON (schema validation). |
| `functionCalls(array)` | function declarations | Enable function calling. |
| `temperature(float)` | 0.0-2.0 | Sampling temperature (model default when omitted). |
| `maxTokens(int)` | token limit | Max output tokens (model default when omitted). |
| `safetySettings(array)` | array | Override the safety thresholds from config. |
| `method(string\|GenerationMethod)` | provider method | Override provider method (`generateContent`, `predict`, `predictLongRunning`). |
| `upload(string $type, string $path)` | (type, local-file-path) | Attach a local file (image/document/audio/video) to the request. |
| `file(string $mimeType, string $uri)` | (MIME type, file URI) | Attach an already-uploaded file. |
| `cache(array $tools = [], array $toolConfig = [], ?string $displayName = null, ?string $ttl = null, ?string $expireTime = null)` | optional params | Create a cache from current builder params and return cache name. |
| `getCache(string $name)` | cache name | Get details of a cached content. |
| `cachedContent(string $name)` | cache name | Use a cached content for generation. |
| `stream(callable)` | callback | Stream chunks (SSE / server events). |
| `generate()` | — | Execute request and return a Response object. |

**Notes on `history` structure**  
History entries follow a `role` + `parts` format:

```php
[
    ['role' => 'user', 'parts' => [['text' => 'User message']]],
    ['role' => 'model', 'parts' => [['text' => 'Assistant reply']]]
]
```

**Text**

```php
$response = Gemini::text()
    ->model('gemini-2.5-flash')
    ->system('You are a helpful assistant.')
    ->prompt('Write a conversation between human and Ai')
    ->history([
        ['role' => 'user', 'parts' => [['text' => 'Hello AI']]],
        ['role' => 'model', 'parts' => [['text' => 'Hello human!']]]
    ])
    ->temperature( 0.7)
    ->maxTokens(1024)
    ->generate();

echo $response->content();
```

**Streaming Responses**

```php
return response()->stream(function () use ($request) {
    Gemini::text()
        ->model('gemini-2.5-flash')
        ->prompt('Tell a long story about artificial intelligence.')
        ->stream(function (array $part) {
            $text = $part['text'] ?? '';
            if (trim($text) !== '') {
                echo "data: " . json_encode(['text' => $text]) . "\n\n";
                ob_flush();
                flush();
            }
        });
}, 200, [
    'Content-Type' => 'text/event-stream',
    'Cache-Control' => 'no-cache',
    'Connection' => 'keep-alive',
    'X-Accel-Buffering' => 'no',
]);
```

**Document Understanding**

```php
$response = Gemini::text()
    ->upload('document', $filePath) // image, video, audio, document
    ->prompt('Extract the key points from this document.')
    ->generate();

echo $response->content();
```

**Structured output**

```php
$response = Gemini::text()
    ->model('gemini-2.5-flash')
    ->structuredSchema([
    'type' => 'object',
    'properties' => [
        'name' => ['type' => 'string'],
        'age'  => ['type' => 'integer']
    ],
    'required' => ['name']
    ])
    ->prompt('Return a JSON object with name and age.')
    ->generate();

$json = $response->content(); // JSON string matching the schema
$data = $response->decode();  // ['name' => ..., 'age' => ...]
```

**Pre-uploaded files**

```php
$uri = Gemini::files()->upload('video', storage_path('app/clip.mp4'));

$response = Gemini::text()
    ->file('video/mp4', $uri)
    ->prompt('Describe this clip.')
    ->generate();
```

---

### ImageBuilder (`Gemini::image()`)

Use for image generation.

| Method | Args | Description |
|---|---:|---|
| `model(string)` | model id | Model for image generation. |
| `prompt(string)` | prompt text | Image description. |
| `method(string)` | e.g. `predict` | Provider method (predict / generateContent). |
| `cache(array $tools = [], array $toolConfig = [], string $displayName = null, string $ttl = null, string $expireTime = null)` | optional params | Create a cache from current builder params and return cache name. |
| `getCache(string $name)` | cache name | Get details of a cached content. |
| `cachedContent(string $name)` | cache name | Use a cached content for generation. |
| `generate()` | — | Run generation. |
| `save($path)` | local path | Save image bytes to disk. |

**Image**

```php
$response = Gemini::image()
    ->model('gemini-2.5-flash-image-preview')
    ->method('generateContent')
    ->prompt('A futuristic city skyline at sunset.')
    ->generate();

$response->save('image.png');
```

---

### VideoBuilder (`Gemini::video()`)

Use for short or long-running video generation.

| Method | Args | Description |
|---|---:|---|
| `model(string)` | model id | Video model. |
| `prompt(string)` | prompt | Describe the video. |
| `cache(array $tools = [], array $toolConfig = [], string $displayName = null, string $ttl = null, string $expireTime = null)` | optional params | Create a cache from current builder params and return cache name. |
| `getCache(string $name)` | cache name | Get details of a cached content. |
| `cachedContent(string $name)` | cache name | Use a cached content for generation. |
| `generate()` | — | Starts the job, polls until done and downloads the video. |
| `save($path)` | local path | Save the downloaded video file. |

```php
$video = Gemini::video()
    ->prompt('A drone shot over a misty forest at sunrise.')
    ->generate();          // blocks while polling (see `long_running` config)

$video->url();             // provider URI of the generated video
$video->save(storage_path('app/forest.mp4'));
```

**Note:** long-running generation uses `predictLongRunning`. Run it in a queued job — polling can take minutes.

---

### AudioBuilder (`Gemini::audio()`)

Use for TTS generation.

| Method | Args | Description |
|---|---:|---|
| `model(string)` | model id | TTS model. |
| `prompt(string)` | text-to-speak | Audio file description |
| `voiceName(string)` | voice id | Select a voice (e.g. `Kore`). |
| `speakerVoices(array)` | speakers array | Speakers (e.g. [['speaker' => 'Joe', 'voiceName' => 'Kore'], ['speaker' => 'Jane', 'voiceName' => 'Puck']]). |
| `cache(array $tools = [], array $toolConfig = [], string $displayName = null, string $ttl = null, string $expireTime = null)` | optional params | Create a cache from current builder params and return cache name. |
| `getCache(string $name)` | cache name | Get details of a cached content. |
| `cachedContent(string $name)` | cache name | Use a cached content for generation. |
| `generate()` | — | Generate audio bytes. |
| `save($path)` | local path | Save generated audio (raw PCM is wrapped as WAV). |

```php
// Single speaker
Gemini::audio()
    ->voiceName('Kore')
    ->prompt('Say cheerfully: Have a wonderful day!')
    ->generate()
    ->save(storage_path('app/greeting.wav'));

// Multiple speakers
Gemini::audio()
    ->speakerVoices([
        ['speaker' => 'Joe', 'voiceName' => 'Kore'],
        ['speaker' => 'Jane', 'voiceName' => 'Puck'],
    ])
    ->prompt("TTS the following conversation:\nJoe: How's it going?\nJane: Great, thanks!")
    ->generate()
    ->save(storage_path('app/dialogue.wav'));
```

---

### Embeddings (`Gemini::embeddings()`)

Accepts a payload array. Typical shape:

```php
$embeddings = Gemini::embeddings([
    'model' => 'gemini-embedding-001',
    'content' => ['parts' => [['text' => 'Text to embed']]],
]);

/* embedding_config */
// https://ai.google.dev/gemini-api/docs/embeddings
// 'embedding_config': {
//     'embedding_config': {
//         'task_type': 'SEMANTIC_SIMILARITY', // SEMANTIC_SIMILARITY, CLASSIFICATION, CLUSTERING, RETRIEVAL_DOCUMENT, RETRIEVAL_QUERY, CODE_RETRIEVAL_QUERY, QUESTION_ANSWERING, FACT_VERIFICATION
//         'embedding_dimensionality': 768 // 128, 256, 512, 768, 1536, 2048
//     }
// }
```
Return value is the raw embeddings structure (provider-specific). Use these vectors for semantic search, similarity, clustering, etc.

---

### Files API (`Gemini::files()`)

High level file manager for uploads used by the "understanding" endpoints.

| Method | Args | Description |
|---|---:|---|
| `upload(string $type, string $localPath)` | `type` in `[document,image,video,audio]` | Upload a local file and return its `uri`. |
| `list(array $params = [])` | `pageSize`, `pageToken` | Return a list of uploaded files (metadata). |
| `get(string $id)` | `abc` or `files/abc` | Get file metadata (name, uri, state, mimeType, displayName). |
| `delete(string $id)` | `abc` or `files/abc` | Delete a previously uploaded file. |

**Files**

```php
// Upload a file
$uri = Gemini::files()->upload('document', $pathToFile);

// List all files
$files = Gemini::files()->list();

// Get file details
$fileInfo = Gemini::files()->get($file_id);

// Delete a file
$success = Gemini::files()->delete($file_id);
```

**Supported file types & MIME**

| Category | Extension | MIME type |
|---|---:|---|
| image | png | image/png |
| image | jpeg | image/jpeg |
| image | jpg | image/jpeg |
| image | webp | image/webp |
| image | heic | image/heic |
| image | heif | image/heif |
| video | mp4 | video/mp4 |
| video | mpeg | video/mpeg |
| video | mov | video/mov |
| video | avi | video/avi |
| video | flv | video/x-flv |
| video | mpg | video/mpg |
| video | webm | video/webm |
| video | wmv | video/wmv |
| video | 3gpp | video/3gpp |
| audio | wav | audio/x-wav |
| audio | mp3 | audio/mp3 |
| audio | aiff | audio/aiff |
| audio | aac | audio/aac |
| audio | ogg | audio/ogg |
| audio | flac | audio/flac |
| document | pdf | application/pdf |
| document | txt | text/plain |
| document | md | text/markdown |

---

### Caching API (`Gemini::caches()`)

High-level cache manager for pre-processing and storing content (prompts, system instructions, history, files) to reuse in generation requests, reducing latency and costs. Caches are model-specific and temporary.

| Method | Args | Description |
|---|---:|---|
| `create(string $model, array $contents, ?string $systemInstruction = null, array $tools = [], array $toolConfig = [], ?string $displayName = null, ?string $ttl = null, ?string $expireTime = null)` | required/optional params | Create a cached content and return CacheResponse. |
| `list(?int $pageSize = null, ?string $pageToken = null)` | optional params | List cached contents (supports pagination). |
| `get(string $name)` | cache name | Get details of a cached content. |
| `update(string $name, ?string $ttl = null, ?string $expireTime = null)` | cache name and expiration | Update cache expiration (TTL or expireTime). |
| `delete(string $name)` | cache name | Delete a cached content. |

**Caching**

```php
// Create a cache
$cache = Gemini::caches()->create(
    model: 'gemini-2.5-flash',
    contents: [['role' => 'user', 'parts' => [['text' => 'Sample content']]]],
    systemInstruction: 'You are a helpful assistant.',
    tools: [], // Optional
    toolConfig: [], // Optional
    displayName: 'My Cache', // Optional
    ttl: '600s' // Optional TTL (e.g., '300s') or expireTime: '2024-12-31T23:59:59Z'
);
$cacheName = $cache->name(); // e.g., 'cachedContents/abc123'

// List all caches
$caches = Gemini::caches()->list(pageSize: 50, pageToken: 'nextPageToken');

// Get cache details
$cacheInfo = Gemini::caches()->get($cacheName);

// Update cache expiration
$updatedCache = Gemini::caches()->update(
    name: $cacheName,
    ttl: '1200s' // Or expireTime: '2024-12-31T23:59:59Z'
);

// Delete a cache
$success = Gemini::caches()->delete($cacheName);
```

**CacheResponse Methods**

- `name()`: Returns the cache name (e.g., 'cachedContents/abc123')
- `displayName()`: Returns the Display Name (e.g., 'Default Cache')
- `model()`: Returns the model used
- `expireTime()`: Returns expiration
- `usageMetadata()`: Returns usage metadata
- `cachedContents()` / `nextPageToken()`: Results and cursor of a `list()` call
- `toArray()`: Full response as array

**Caching in Generation Builders**

Caching is also integrated into text, image, video, and audio builders for seamless use:

```php
// Create cache from builder params
$cacheName = Gemini::text()
    ->model('gemini-2.5-flash')
    ->prompt('Sample prompt')
    ->system('System instruction')
    ->history([['role' => 'user', 'parts' => [['text' => 'History item']]]]) // optional
    ->cache(
        tools: [], // optional
        toolConfig: [], // optional
        displayName: 'My Cache', // optional
        ttl: '600s' // optional, or expireTime
    );

// Get cache details from builder
$cacheInfo = Gemini::text()->getCache($cacheName);

// Use cached content in generation
$response = Gemini::text()
    ->prompt('Summarize this.')
    ->cachedContent($cacheName)
    ->generate();
```

For more details, refer to the [Gemini API Caching Documentation](https://ai.google.dev/api/caching).

---

## Streaming (Server-Sent Events)
The `stream` route uses `Content-Type: text/event-stream`. Connect from a browser or SSE client and consume `data: <json>` messages per chunk.

---

### Streaming behaviour

- Implemented using SSE (Server-Sent Events, `alt=sse`). The callback is invoked once per returned part, typically `['text' => '...']`.
- Client should reconnect behaviorally for resilience and handle partial chunks.
- Use response headers:
  - `Content-Type: text/event-stream`
  - `Cache-Control: no-cache`
  - `Connection: keep-alive`
  - `X-Accel-Buffering: no`

---

## Tips, error handling & best practices

- Respect provider limits — pick appropriate `maxTokens` and `temperature`.  
- For large media (video) prefer long-running `predictLongRunning` models and rely on `save()` to poll and download final asset.  
- Use `safetySettings` from config for content filtering. You can override per-request.  
- When uploading user-supplied files, validate MIME type and size before calling `Gemini::files()->upload`.  
- For caching, use TTL wisely to avoid expired caches; always check expiration in responses.

---

## Artisan Commands

The package includes helpful Artisan commands:

| Command                      | Description                 |
|------------------------------|-----------------------------|
| `php artisan gemini:models`  | List available models.      |

---

## Testing

```bash
composer test
```

## Changelog

See [CHANGELOG](CHANGELOG.md) for what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details. Issues and pull requests: [github.com/siberfx/laravel-gemini](https://github.com/siberfx/laravel-gemini).

## Credits

- [Selim Görmüş (siberfx)](https://github.com/siberfx) — maintainer
- Hossein Hezami — original author

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
