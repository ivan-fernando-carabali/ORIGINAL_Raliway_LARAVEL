@extends('layouts.app')

@section('title', 'Inicio - Smart Inventory')

@section('content')
<div class="container">
  <div class="left">
    <h1>Welcome</h1>
    <h3 class="highlight">Smart Inventory</h3>
    <p>
      Aquí facilitamos la gestión de inventarios con una plataforma intuitiva que ofrece visibilidad en tiempo real
      y análisis de datos, ayudando a las empresas a maximizar rentabilidad y reducir desperdicios.
    </p>
    <div class="logo">
      <img src="https://img.icons8.com/ios-filled/500/f1c40f/combo-chart.png" alt="Logo">
      <h2>SMART INVENTORY</h2>
      <small>Controlando el presente, planificando el futuro</small>
    </div>
  </div>

  <div class="right">
    <h2>Regístrate</h2>
    <form autocomplete="off" action="/register" method="POST">
      <div class="form-group"><input type="text" placeholder="Nombre" required /></div>
      <div class="form-group"><input type="text" placeholder="Apellido" required /></div>
      <div class="form-group"><input type="text" placeholder="Email" required /></div>
      <div class="form-group"><input type="text" placeholder="Contraseña" required /></div>
      <button type="submit" class="btn">REGISTRARME</button>
    </form>
    <div class="social-icons">
      <i class="fab fa-google"></i>
      <i class="fab fa-facebook"></i>
      <i class="fab fa-apple"></i>
      <i class="fab fa-yahoo"></i>
    </div>
  </div>
</div>
<a href="/dashboard" class="btn_entrar">Entrar a la aplicación</a>
@endsection
