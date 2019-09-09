@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">{{ trans('tm_login.login_teacher') }}</div>
                <div class="panel-body">
                    <form class="form-horizontal" method="POST" action="{{ route('tm_login') }}">
                        {{ csrf_field() }}

                        <div class="form-group{{ $errors->has('username') ? ' has-error' : '' }}">
                            <label for="email" class="col-md-4 control-label">{{ trans('tm_login.teacher_user_name') }}</label>

                            <div class="col-md-6">
                                <input id="username" type="text" class="form-control" name="username" value="" required autofocus>

                                @if ($errors->has('username'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('username') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                            <label for="password" class="col-md-4 control-label">{{ trans('tm_login.password') }}</label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control" name="password" required>

                                @if ($errors->has('password'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-md-8 col-md-offset-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ trans('tm_login.login') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>

        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">{{ trans('tm_login.login_activation_code') }}</div>
                <div class="panel-body">
                    <form class="form-horizontal" method="POST" action="{{ route('register_activation_code') }}">
                        {{ csrf_field() }}

                        <div class="form-group{{ $errors->has('activation_code') ? ' has-error' : '' }}">
                            <label for="activation_code" class="col-md-4 control-label">{{ trans('tm_login.activation_code') }}</label>

                            <div class="col-md-6">
                                <input id="activation_code" type="text" class="form-control" name="activation_code" value="" required autofocus>

                                @if ($errors->has('activation_code'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('activation_code') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-md-8 col-md-offset-4">
                                @if ($errors->has('team_model_id'))
                                    <span class="help-block">
                                        <strong>
                                            <p class="text-danger">{{ $errors->first('team_model_id') }}</p>
                                        </strong>
                                    </span>
                                @endif
                                <button type="submit" class="btn btn-primary">
                                    {{ trans('tm_login.login') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
