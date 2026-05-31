import 'package:flutter_bloc/flutter_bloc.dart';
import '../../data/repositories/auth_repository.dart';
import '../../core/storage/secure_storage.dart';
import 'auth_event.dart';
import 'auth_state.dart';

class AuthBloc extends Bloc<AuthEvent, AuthState> {
  final AuthRepository _authRepository;
  final SecureStorage _secureStorage;

  AuthBloc({
    required AuthRepository authRepository,
    required SecureStorage secureStorage,
  })  : _authRepository = authRepository,
        _secureStorage = secureStorage,
        super(const AuthInitial()) {
    on<CheckAuthStatus>(_onCheckAuthStatus);
    on<LoginRequested>(_onLoginRequested);
    on<RegisterRequested>(_onRegisterRequested);
    on<OtpVerifyRequested>(_onOtpVerifyRequested);
    on<LogoutRequested>(_onLogoutRequested);
    on<BiometricLoginRequested>(_onBiometricLoginRequested);
    on<ResendOtpRequested>(_onResendOtpRequested);
  }

  Future<void> _onCheckAuthStatus(
    CheckAuthStatus event,
    Emitter<AuthState> emit,
  ) async {
    final token = await _secureStorage.getToken();
    if (token == null || token.isEmpty) {
      emit(const AuthUnauthenticated());
      return;
    }

    final response = await _authRepository.getUser();
    if (response.success && response.data != null) {
      emit(AuthAuthenticated(user: response.data!));
    } else {
      await _secureStorage.deleteToken();
      emit(const AuthUnauthenticated());
    }
  }

  Future<void> _onLoginRequested(
    LoginRequested event,
    Emitter<AuthState> emit,
  ) async {
    emit(const AuthLoading());

    final response = await _authRepository.login(event.email, event.password);

    if (response.success && response.data != null) {
      final data = response.data!;
      final requiresOtp = data['requires_otp'] as bool? ?? false;

      if (requiresOtp) {
        emit(AuthOtpRequired(email: event.email));
      } else {
        // Token was already saved by the repository — fetch user profile
        final userResponse = await _authRepository.getUser();
        if (userResponse.success && userResponse.data != null) {
          emit(AuthAuthenticated(user: userResponse.data!));
        } else {
          emit(AuthError(
              message: userResponse.errorMessage.isNotEmpty
                  ? userResponse.errorMessage
                  : 'Failed to load user profile'));
        }
      }
    } else {
      emit(AuthError(message: response.errorMessage));
    }
  }

  Future<void> _onRegisterRequested(
    RegisterRequested event,
    Emitter<AuthState> emit,
  ) async {
    emit(const AuthLoading());

    final response = await _authRepository.register({
      'name': event.name,
      'email': event.email,
      'phone': event.phone,
      'branch': event.branch,
      'password': event.password,
      'password_confirmation': event.passwordConfirmation,
    });

    if (response.success && response.data != null) {
      // Registration returns a token directly — user is logged in
      final token = response.data!['token'] as String?;
      if (token != null) {
        // Token already saved by repository, fetch user profile
        final userResponse = await _authRepository.getUser();
        if (userResponse.success && userResponse.data != null) {
          emit(AuthAuthenticated(user: userResponse.data!));
        } else {
          emit(AuthError(
              message: userResponse.errorMessage.isNotEmpty
                  ? userResponse.errorMessage
                  : 'Account created but failed to load profile'));
        }
      } else {
        // Fallback: if somehow no token, go to OTP
        emit(AuthOtpRequired(email: event.email));
      }
    } else {
      emit(AuthError(message: response.errorMessage));
    }
  }

  Future<void> _onOtpVerifyRequested(
    OtpVerifyRequested event,
    Emitter<AuthState> emit,
  ) async {
    emit(const AuthLoading());

    final response = await _authRepository.verifyOtp(event.email, event.code);

    if (response.success && response.data != null) {
      final userResponse = await _authRepository.getUser();
      if (userResponse.success && userResponse.data != null) {
        emit(AuthAuthenticated(user: userResponse.data!));
      } else {
        emit(const AuthError(message: 'Failed to load user profile'));
      }
    } else {
      emit(AuthError(message: response.errorMessage));
    }
  }

  Future<void> _onLogoutRequested(
    LogoutRequested event,
    Emitter<AuthState> emit,
  ) async {
    await _authRepository.logout();
    await _secureStorage.deleteToken();
    emit(const AuthUnauthenticated());
  }

  Future<void> _onBiometricLoginRequested(
    BiometricLoginRequested event,
    Emitter<AuthState> emit,
  ) async {
    emit(const AuthLoading());

    final biometricToken = await _secureStorage.getBiometricToken();
    if (biometricToken == null || biometricToken.isEmpty) {
      emit(const AuthError(message: 'Biometric login not set up'));
      return;
    }

    final response = await _authRepository.biometricLogin(biometricToken);
    if (response.success) {
      final userResponse = await _authRepository.getUser();
      if (userResponse.success && userResponse.data != null) {
        emit(AuthAuthenticated(user: userResponse.data!));
      } else {
        emit(const AuthError(message: 'Failed to load user profile'));
      }
    } else {
      emit(AuthError(message: response.errorMessage));
    }
  }

  Future<void> _onResendOtpRequested(
    ResendOtpRequested event,
    Emitter<AuthState> emit,
  ) async {
    // We don't emit AuthLoading because that would trigger a full screen loader
    // and potentially disrupt the timer/UI state on the OtpScreen.
    final response = await _authRepository.resendOtp(event.email);

    if (response.success) {
      // Emit a "success" state that the UI can listen for to show a SnackBar.
      // Since we don't have a dedicated ResendOtpSuccess state,
      // we can use AuthOtpRequired to effectively "refresh" the screen
      // or just maintain current state.
      // However, for a clean implementation, we'll emit a temporary error state
      // if it fails, or just let the UI handle the success via a listener.

      // To avoid displacing the user from the OTP screen, we emit
      // AuthOtpRequired again to signal a successful refresh of the OTP request.
      emit(AuthOtpRequired(email: event.email));
    } else {
      emit(AuthError(message: response.errorMessage));
    }
  }
}
