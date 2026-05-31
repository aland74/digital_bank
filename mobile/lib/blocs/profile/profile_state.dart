import 'package:equatable/equatable.dart';

abstract class ProfileState extends Equatable {
  const ProfileState();

  @override
  List<Object?> get props => [];
}

class ProfileInitial extends ProfileState {}

class ProfileLoading extends ProfileState {}

class ProfileLoaded extends ProfileState {
  final dynamic user; // Using dynamic for now to keep it simple, or use User model
  const ProfileLoaded(this.user);

  @override
  List<Object?> get props => [user];
}

class ProfileError extends ProfileState {
  final String message;
  const ProfileError(this.message);

  @override
  List<Object?> get props => [message];
}

class KycUploading extends ProfileState {}

class KycUploadSuccess extends ProfileState {
  final String message;
  const KycUploadSuccess(this.message);

  @override
  List<Object?> get props => [message];
}

class KycUploadFailure extends ProfileState {
  final String message;
  const KycUploadFailure(this.message);

  @override
  List<Object?> get props => [message];
}
