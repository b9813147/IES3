@extends('errors::layout')

@section('title', 'Error')

@section('message', $exception->getMessage())