@include('errors.layout', [
  'code' => 429,
  'heading' => 'Too many tries',
  'text' => "That was a lot of requests in a short time. Wait a minute, then try again.",
  'retry' => true,
])
