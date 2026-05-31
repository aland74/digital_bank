import 'package:equatable/equatable.dart';
import '../../data/models/user.dart';

abstract class AuthState extends Equatable {
  const AuthState();

  @override
  List<Object?> get props => [];
}

class AuthInitial extends AuthState {
  const AuthInitial();
}

class AuthLoading extends AuthState {
  const AuthLoading();
}

class AuthAuthenticated extends AuthState {
  final User user;

  const AuthAuthenticated({required this.user});

  @override
  List<Object?> get props => [user];
}

class AuthOtpRequired extends AuthState {
  final String email;

  const AuthOtpRequired({required this.email});

  @override
  List<Object?> get props => [email];
}

class AuthOtpResent extends AuthOtpRequired {
  const AuthOtpResent({required super.email});
}

class AuthUnauthenticated extends AuthState {
  const AuthUnauthenticated();
}

class AuthError extends AuthState {
  final String message;

  const AuthError({required this.message});

  @override
  List<Object?> get props => [message];
}
