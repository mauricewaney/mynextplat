@extends('layouts.app')
@section('title', 'Report an Issue | ' . config('app.name', 'MyNextPlat'))
@section('description', 'Report incorrect game data, missing information, or broken links on MyNextPlat.')
@section('meta')
<meta name="robots" content="noindex">
@endsection
@section('content')
<div id="vue-report-issue"></div>
@endsection
