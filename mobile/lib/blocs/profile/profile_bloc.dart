import 'package:flutter_bloc/flutter_bloc.dart';
import '../../data/repositories/profile_repository.dart';
import 'profile_event.dart';
import 'profile_state.dart';

class ProfileBloc extends Bloc<ProfileEvent, ProfileState> {
  final ProfileRepository _profileRepository;

  ProfileBloc(this._profileRepository) : super(ProfileInitial()) {
    on<GetProfileRequested>(_onGetProfileRequested);
    on<UpdateProfileRequested>(_onUpdateProfileRequested);
    on<ChangePasswordRequested>(_onChangePasswordRequested);
    on<UploadKycRequested>(_onUploadKycRequested);
  }

  Future<void> _onGetProfileRequested(GetProfileRequested event, Emitter<ProfileState> emit) async {
    emit(ProfileLoading());
    final result = await _profileRepository.getProfile();
    if (result.success) {
      emit(ProfileLoaded(result.data));
    } else {
      emit(ProfileError(result.message ?? 'An unexpected error occurred'));
    }
  }

  Future<void> _onUpdateProfileRequested(UpdateProfileRequested event, Emitter<ProfileState> emit) async {
    emit(ProfileLoading());
    final result = await _profileRepository.updateProfile(event.data);
    if (result.success) {
      emit(ProfileLoaded(result.data));
    } else {
      emit(ProfileError(result.message ?? 'An unexpected error occurred'));
    }
  }

  Future<void> _onChangePasswordRequested(ChangePasswordRequested event, Emitter<ProfileState> emit) async {
    emit(ProfileLoading());
    final result = await _profileRepository.changePassword({
      'current_password': event.currentPassword ?? '',
      'new_password': event.newPassword ?? '',
      'confirm_password': event.confirmPassword ?? '',
    });
    if (result.success) {
      emit(ProfileLoaded(null)); // Or a specific success state
    } else {
      emit(ProfileError(result.message ?? 'An unexpected error occurred'));
    }
  }

  Future<void> _onUploadKycRequested(UploadKycRequested event, Emitter<ProfileState> emit) async {
    emit(KycUploading());
    final result = await _profileRepository.uploadKyc(
      documentType: event.documentType ?? '',
      documentNumber: event.documentNumber ?? '',
      file: event.file,
    );
    if (result.success) {
      emit(KycUploadSuccess(result.message ?? 'KYC uploaded successfully'));
    } else {
      emit(KycUploadFailure(result.message ?? 'KYC upload failed'));
    }
  }
}
