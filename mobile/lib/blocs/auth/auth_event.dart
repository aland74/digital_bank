import 'package:equatable/equatable.dart';

abstract class AuthEvent extends Equatable {
  const AuthEvent();

  @override
  List<Object?> get props => [];
}

class CheckAuthStatus extends AuthEvent {
  const CheckAuthStatus();
}

class LoginRequested extends AuthEvent {
  final String email;
  final String password;

  const LoginRequested({required this.email, required this.password});

  @override
  List<Object?> get props => [email, password];
}

class RegisterRequested extends AuthEvent {
  final String name;
  final String email;
  final String phone;
  final String branch;
  final String password;
  final String passwordConfirmation;

  const RegisterRequested({
    required this.name,
    required this.email,
    required this.phone,
    required this.branch,
    required this.password,
    required this.passwordConfirmation,
  });

  @override
  List<Object?> get props => [name, email, phone, branch, password];
}

class OtpVerifyRequested extends AuthEvent {
  final String email;
  final String code;

  const OtpVerifyRequested({required this.email, required this.code});

  @override
  List<Object?> get props => [email, code];
}

class LogoutRequested extends AuthEvent {
  const LogoutRequested();
}

class BiometricLoginRequested extends AuthEvent {
  const BiometricLoginRequested();
}

class ResendOtpRequested extends AuthEvent {
  final String email;

  const ResendOtpRequested({required this.email});

  @override
  List<Object?> get props => [email];
}
