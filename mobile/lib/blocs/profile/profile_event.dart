import 'package:equatable/equatable.dart';
import 'dart:io';

abstract class ProfileEvent extends Equatable {
  const ProfileEvent();

  @override
  List<Object?> get props => [];
}

class GetProfileRequested extends ProfileEvent {}

class UpdateProfileRequested extends ProfileEvent {
  final Map<String, dynamic> data;
  const UpdateProfileRequested(this.data);

  @override
  List<Object?> get props => [data];
}

class ChangePasswordRequested extends ProfileEvent {
  final String currentPassword;
  final String newPassword;
  final String confirmPassword;

  const ChangePasswordRequested({
    required this.currentPassword,
    required this.newPassword,
    required this.confirmPassword,
  });

  @override
  List<Object?> get props => [currentPassword, newPassword, confirmPassword];
}

class UploadKycRequested extends ProfileEvent {
  final String documentType;
  final String documentNumber;
  final File file;

  const UploadKycRequested({
    required this.documentType,
    required this.documentNumber,
    required this.file,
  });

  @override
  List<Object?> get props => [documentType, documentNumber, file];
}
