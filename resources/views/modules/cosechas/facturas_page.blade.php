@extends('layouts.main')

@section('titulo', $titulo ?? 'Factura de cosecha')

@section('contenido')
    @include('modules.cosechas.facturas', ['renderInModal' => false])
@endsection