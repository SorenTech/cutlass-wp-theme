@extends('layouts.master')

@section('content')
	<div class="container">
		<h1>{{ $title }}</h1>
		@wploop
			<h4><a href="{{ get_permalink() }}">{{ $post->post_title }}</a></h4>
		@wpempty
			<h5>Sorry, No Posts</h5>
		@wpend
	</div>
@endsection