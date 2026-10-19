@include('errors.layout', [
  'code' => 500,
  'heading' => 'Something went wrong on our side',
  'text' => "It's not you. We've logged the problem; please try again in a few minutes.",
  'retry' => true,
])
