@extends('layouts.app')
@section('title', 'Profile | ' . config('app.name', 'MyNextPlat'))
@section('content')
<div id="vue-profile" data-identifier="{{ $identifier }}"></div>
@endsection
