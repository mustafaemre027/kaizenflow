@extends('errors::minimal')

@section('title', 'Bu işlemi yapmaya yetkiniz bulunmuyor.')
@section('code', '403')
@section('message', __($exception->getMessage() ?: 'Forbidden'))
