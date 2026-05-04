import 'package:flutter/foundation.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:dio/dio.dart';
import '../../core/api_service.dart';
import '../../core/cache_service.dart';
import '../../core/storage_keys.dart';

// --- Events ---
abstract class AuthEvent {}
class LoginRequested extends AuthEvent {
  final String email;
  final String password;
  LoginRequested(this.email, this.password);
}
class LogoutRequested extends AuthEvent {}
class AppStarted extends AuthEvent {}
class TenantSelected extends AuthEvent {
  final int teamId;
  TenantSelected(this.teamId);
}

class TokenLoginRequested extends AuthEvent {
  final String token;
  TokenLoginRequested(this.token);
}

class WhatsAppNumberSelected extends AuthEvent {
  final String numberId;
  WhatsAppNumberSelected(this.numberId);
}

class MemberSelected extends AuthEvent {
  final String memberId;
  MemberSelected(this.memberId);
}

class OtpRequested extends AuthEvent {
  final String mobile;
  OtpRequested(this.mobile);
}

class OtpLoginRequested extends AuthEvent {
  final String mobile;
  final String otp;
  OtpLoginRequested(this.mobile, this.otp);
}

// --- States ---
abstract class AuthState {}
class AuthInitial extends AuthState {}
class AuthLoading extends AuthState {}
class Authenticated extends AuthState {
  final String token;
  final Map<String, dynamic>? user;
  final Map<String, dynamic>? businessProfile;
  final List<dynamic> teams;
  final List<dynamic> whatsappNumbers;
  final List<dynamic> members;
  final String? activeNumberId;
  final String? activeMemberId;
  final String? tenantId;
  
  Authenticated(this.token, {
    this.user, 
    this.businessProfile,
    this.teams = const [], 
    this.whatsappNumbers = const [],
    this.members = const [],
    this.activeNumberId,
    this.activeMemberId,
    this.tenantId,
  });
}
class Unauthenticated extends AuthState {}
class AuthError extends AuthState {
  final String message;
  AuthError(this.message);
}

class OtpSent extends AuthState {
  final String mobile;
  OtpSent(this.mobile);
}
class TenantSelectionRequired extends AuthState {
  final String token;
  final List<dynamic> teams;
  TenantSelectionRequired(this.token, this.teams);
}

class WhatsAppNumberSelectionRequired extends AuthState {
  final String token;
  final List<dynamic> numbers;
  WhatsAppNumberSelectionRequired(this.token, this.numbers);
}

// --- BLoC ---
class AuthBloc extends Bloc<AuthEvent, AuthState> {
  final ApiService _apiService;
  final FlutterSecureStorage _storage = const FlutterSecureStorage();

  AuthBloc(this._apiService) : super(AuthInitial()) {
    on<AppStarted>(_onAppStarted);
    on<LoginRequested>(_onLoginRequested);
    on<TokenLoginRequested>(_onTokenLoginRequested);
    on<LogoutRequested>(_onLogoutRequested);
    on<TenantSelected>(_onTenantSelected);
    on<WhatsAppNumberSelected>(_onWhatsAppNumberSelected);
    on<MemberSelected>(_onMemberSelected);
    on<OtpRequested>(_onOtpRequested);
    on<OtpLoginRequested>(_onOtpLoginRequested);
  }

  Future<void> _onTokenLoginRequested(TokenLoginRequested event, Emitter<AuthState> emit) async {
    if (state is AuthLoading) return;

    if (kDebugMode) {
      debugPrint('[DEBUG] [AUTH] Processing token login request');
    }
    emit(AuthLoading());
    try {
      // 1. Save it for future requests
      if (kDebugMode) {
        debugPrint('🔒 [AUTH] Step 1: Persisting token to secure storage...');
      }
      await _storage.write(key: StorageKeys.authToken, value: event.token);
      await _storage.write(key: StorageKeys.lastLoginTimestamp, value: DateTime.now().toIso8601String());
      
      // Verification: Ensure storage write completed
      final savedToken = await _storage.read(key: StorageKeys.authToken);
      if (savedToken != event.token) {
        throw Exception('Storage verification failed. Token could not be persisted safely.');
      }
      
      // 2. Ensure ApiService uses the latest baseUrl from ConfigService (after QR scan)
      await _apiService.reloadConfig();

      // 3. Finalize the login (this transitions the flow state on the backend)
      if (kDebugMode) {
        debugPrint('🔒 [AUTH] Step 2: Verifying session via /finalize...');
      }
      
      final Response me;
      try {
        me = await _apiService.finalizeLogin(); 
      } on DioException catch (e) {
        final code = e.response?.statusCode;
        final body = e.response?.data;
        debugPrint('❌ [AUTH] /finalize failed: $code - $body');
        throw Exception('Verification failed (HTTP $code): ${body?['message'] ?? e.message}');
      }

      if (kDebugMode) {
        debugPrint('🔒 [AUTH] Step 3: Parsing user data. Status: ${me.statusCode}');
      }
      
      final user = me.data['user'] as Map<String, dynamic>?;
      final teams = (me.data['teams'] as List?) ?? [];
      final members = (me.data['members'] as List?) ?? [];
      final numbers = (me.data['numbers'] as List?) ?? [];
      final businessProfile = me.data['business_profile'] as Map<String, dynamic>?;

      // Strict Verification: Ensure we have a valid user
      if (user == null || user['id'] == null) {
         throw Exception('Invalid user data: Server responded success but user profile is missing.');
      }

      if (kDebugMode) {
        debugPrint('✅ [AUTH] Strict login successful for ${user['email']}');
      }

      String? activeNumberId;
      if (numbers.isNotEmpty) {
        if (numbers.length == 1) {
          activeNumberId = numbers.first['id'].toString();
          await _storage.write(key: StorageKeys.activeWhatsAppNumberId, value: activeNumberId);
        } else {
          emit(WhatsAppNumberSelectionRequired(event.token, numbers));
          return;
        }
      }

      if (teams.length == 1) {
        await _storage.write(key: StorageKeys.tenantId, value: teams.first['id'].toString());
        emit(Authenticated(event.token, user: user, businessProfile: businessProfile, teams: teams, members: members, whatsappNumbers: numbers, activeNumberId: activeNumberId, tenantId: teams.first['id'].toString()));
        return;
      }
      
      if (teams.isEmpty) {
        throw Exception('Access Denied: Your account is not associated with any active teams/organizations.');
      }

      emit(TenantSelectionRequired(event.token, teams));
    } catch (e) {
      if (kDebugMode) {
        debugPrint('❌ [AUTH] Strict login failed: $e');
      }
      // Revert token storage on failure to prevent stale sessions
      await _storage.delete(key: StorageKeys.authToken);
      
      String displayError = e.toString().replaceFirst('Exception: ', '');
      emit(AuthError(displayError));
    }
  }

  Future<void> _onAppStarted(AppStarted event, Emitter<AuthState> emit) async {
    final token = await _storage.read(key: StorageKeys.authToken);
    if (token != null) {
      final tenantId = await _storage.read(key: StorageKeys.tenantId);
      
      try {
        // Always try to fetch fresh user info to verify the token is still valid
        final me = await _apiService.getMe();
        final teams = (me.data['teams'] as List?) ?? [];
        final members = (me.data['members'] as List?) ?? [];
        final user = me.data['user'] as Map<String, dynamic>?;
        final businessProfile = me.data['business_profile'] as Map<String, dynamic>?;

        // Fetch numbers
        final numbersResp = await _apiService.getWhatsAppNumbers();
        final numbers = numbersResp.data as List;
        final savedNumberId = await _storage.read(key: StorageKeys.activeWhatsAppNumberId);
        final savedMemberId = await _storage.read(key: StorageKeys.activeMemberId);
        
        // Use saved number if valid, otherwise fallback to first one
        String? activeNumberId = savedNumberId;
        if (activeNumberId == null && numbers.isNotEmpty) {
          activeNumberId = numbers.first['id'].toString();
          await _storage.write(key: StorageKeys.activeWhatsAppNumberId, value: activeNumberId);
        }

        if (tenantId != null) {
          emit(Authenticated(
            token, 
            user: user, 
            businessProfile: businessProfile,
            teams: teams, 
            whatsappNumbers: numbers, 
            members: members,
            activeNumberId: activeNumberId,
            activeMemberId: savedMemberId,
            tenantId: tenantId,
          ));
          return;
        }

        if (teams.length == 1) {
          await _storage.write(key: StorageKeys.tenantId, value: teams.first['id'].toString());
          emit(Authenticated(
            token, 
            user: user, 
            businessProfile: businessProfile,
            teams: teams, 
            whatsappNumbers: numbers, 
            members: members,
            activeNumberId: activeNumberId,
            activeMemberId: savedMemberId,
            tenantId: teams.first['id'].toString(),
          ));
          return;
        }
        
        emit(TenantSelectionRequired(token, teams));
      } on DioException catch (e) {
        if (e.response?.statusCode == 401) {
          // Token expired or invalid
          await _storage.delete(key: StorageKeys.authToken);
          await _storage.delete(key: StorageKeys.tenantId);
          emit(Unauthenticated());
        } else {
          // Network error, but we have a token - allow offline access with cached token
          emit(Authenticated(token));
        }
      } catch (_) {
        // Generic error, fallback to authenticated if we have a token
        emit(Authenticated(token));
      }
    } else {
      emit(Unauthenticated());
    }
  }


  Future<void> _onLoginRequested(LoginRequested event, Emitter<AuthState> emit) async {
    if (state is AuthLoading) return;

    if (kDebugMode) {
      debugPrint('[DEBUG] [AUTH] Processing email/password login request');
    }
    emit(AuthLoading());
    try {
      final response = await _apiService.login(event.email, event.password);
      if (kDebugMode) {
        debugPrint('[DEBUG] [AUTH] Login response: ${response.statusCode}');
        debugPrint('[DEBUG] [AUTH] Login body summary: ${response.data}');
      }
      final token = response.data['token'];
      final teams = (response.data['teams'] as List?) ?? [];
      final members = (response.data['members'] as List?) ?? [];
      final user = response.data['user'] as Map<String, dynamic>?;
      final businessProfile = response.data['business_profile'] as Map<String, dynamic>?;
      if (token is! String || token.isEmpty) {
        emit(AuthError('Invalid credentials'));
        return;
      }

      await _storage.write(key: StorageKeys.authToken, value: token);

      // Fetch available WhatsApp numbers
      List<dynamic> numbers = [];
      String? activeNumberId;
      try {
        final numResp = await _apiService.getWhatsAppNumbers();
        numbers = numResp.data as List;
        if (numbers.isNotEmpty) {
          if (numbers.length == 1) {
            activeNumberId = numbers.first['id'].toString();
            await _storage.write(key: StorageKeys.activeWhatsAppNumberId, value: activeNumberId);
          } else {
            emit(WhatsAppNumberSelectionRequired(token, numbers));
            return;
          }
        }
      } catch (_) {}

      if (teams.length == 1) {
        if (kDebugMode) {
          debugPrint('[DEBUG] [AUTH] Single team found, auto-selecting tenant: ${teams.first['id']}');
        }
        await _storage.write(key: StorageKeys.tenantId, value: teams.first['id'].toString());
        emit(Authenticated(token, user: user, businessProfile: businessProfile, teams: teams, whatsappNumbers: numbers, members: members, activeNumberId: activeNumberId, tenantId: teams.first['id'].toString()));
        return;
      }

      final existingTenantId = await _storage.read(key: StorageKeys.tenantId);
      if (existingTenantId != null) {
        emit(Authenticated(token, user: user, businessProfile: businessProfile, teams: teams, whatsappNumbers: numbers, members: members, activeNumberId: activeNumberId, tenantId: existingTenantId));
        return;
      }


      emit(TenantSelectionRequired(token, teams));

    } on DioException catch (e) {
      String message = 'Login failed';
      if (e.response != null && e.response?.data is Map) {
        message = e.response?.data['message'] ?? 'Invalid credentials';
      } else {
        message = e.message ?? 'Server connection error';
      }
      emit(AuthError(message));
    } catch (e) {
      emit(AuthError('An unexpected error occurred: ${e.toString()}'));
    }
  }

  Future<void> _onLogoutRequested(LogoutRequested event, Emitter<AuthState> emit) async {
    if (!kIsWeb) {
      try {
        await _apiService.logout();
      } catch (_) {}
      await _storage.delete(key: StorageKeys.authToken);
      await _storage.delete(key: StorageKeys.tenantId);
    }
    emit(Unauthenticated());
  }

  Future<void> _onTenantSelected(TenantSelected event, Emitter<AuthState> emit) async {
    final token = await _storage.read(key: StorageKeys.authToken);
    if (token == null) {
      emit(Unauthenticated());
      return;
    }
    await _storage.write(key: StorageKeys.tenantId, value: event.teamId.toString());
    await _storage.delete(key: StorageKeys.activeWhatsAppNumberId);
    
    // Clear local cache for new tenant
    await CacheService.clearAllCache();
    
    // Try to get user details and numbers now that we have a tenant
    try {
      final me = await _apiService.getMe();
      final user = me.data['user'] as Map<String, dynamic>?;
      final teams = (me.data['teams'] as List?) ?? [];
      final members = (me.data['members'] as List?) ?? [];
      final businessProfile = me.data['business_profile'] as Map<String, dynamic>?;
      
      final numResp = await _apiService.getWhatsAppNumbers();
      final numbers = numResp.data as List;
      String? activeNumberId;
      if (numbers.isNotEmpty) {
        if (numbers.length == 1) {
          activeNumberId = numbers.first['id'].toString();
          await _storage.write(key: StorageKeys.activeWhatsAppNumberId, value: activeNumberId);
        } else {
          emit(WhatsAppNumberSelectionRequired(token, numbers));
          return;
        }
      }

      emit(Authenticated(
        token, 
        user: user, 
        businessProfile: businessProfile,
        teams: teams, 
        whatsappNumbers: numbers, 
        members: members,
        activeNumberId: activeNumberId,
        tenantId: event.teamId.toString(),
      ));
    } catch (_) {
      emit(Authenticated(token));
    }
  }

  Future<void> _onWhatsAppNumberSelected(WhatsAppNumberSelected event, Emitter<AuthState> emit) async {
    final currentState = state;
    if (currentState is Authenticated) {
      await _storage.write(key: StorageKeys.activeWhatsAppNumberId, value: event.numberId);
      emit(Authenticated(
        currentState.token,
        user: currentState.user,
        businessProfile: currentState.businessProfile,
        teams: currentState.teams,
        whatsappNumbers: currentState.whatsappNumbers,
        members: currentState.members,
        activeNumberId: event.numberId,
        activeMemberId: currentState.activeMemberId,
        tenantId: currentState.tenantId,
      ));
    } else if (currentState is WhatsAppNumberSelectionRequired) {
      await _storage.write(key: StorageKeys.activeWhatsAppNumberId, value: event.numberId);
      
      try {
        final me = await _apiService.getMe();
        final user = me.data['user'] as Map<String, dynamic>?;
        final teams = (me.data['teams'] as List?) ?? [];
        final members = (me.data['members'] as List?) ?? [];
        final businessProfile = me.data['business_profile'] as Map<String, dynamic>?;
        
        emit(Authenticated(
          currentState.token, 
          user: user, 
          businessProfile: businessProfile,
          teams: teams, 
          whatsappNumbers: currentState.numbers, 
          members: members,
          activeNumberId: event.numberId,
          tenantId: await _storage.read(key: StorageKeys.tenantId),
        ));
      } catch (_) {
        emit(Authenticated(currentState.token, whatsappNumbers: currentState.numbers, activeNumberId: event.numberId));
      }
    }
  }

  Future<void> _onMemberSelected(MemberSelected event, Emitter<AuthState> emit) async {
    final currentState = state;
    if (currentState is Authenticated) {
      if (event.memberId == 'self') {
        await _storage.delete(key: StorageKeys.activeMemberId);
      } else {
        await _storage.write(key: StorageKeys.activeMemberId, value: event.memberId);
      }
      
      emit(Authenticated(
        currentState.token,
        user: currentState.user,
        businessProfile: currentState.businessProfile,
        teams: currentState.teams,
        whatsappNumbers: currentState.whatsappNumbers,
        members: currentState.members,
        activeNumberId: currentState.activeNumberId,
        activeMemberId: event.memberId == 'self' ? null : event.memberId,
        tenantId: currentState.tenantId,
      ));
    }
  }

  Future<void> _onOtpRequested(OtpRequested event, Emitter<AuthState> emit) async {
    emit(AuthLoading());
    try {
      await _apiService.requestOtp(event.mobile);
      emit(OtpSent(event.mobile));
    } on DioException catch (e) {
      emit(AuthError(e.response?.data['message'] ?? 'Failed to send OTP'));
    } catch (_) {
      emit(AuthError('Something went wrong'));
    }
  }

  Future<void> _onOtpLoginRequested(OtpLoginRequested event, Emitter<AuthState> emit) async {
    emit(AuthLoading());
    try {
      final response = await _apiService.loginWithOtp(event.mobile, event.otp);
      final token = response.data['token'];
      final teams = (response.data['teams'] as List?) ?? [];
      final members = (response.data['members'] as List?) ?? [];
      final user = response.data['user'] as Map<String, dynamic>?;
      final businessProfile = response.data['business_profile'] as Map<String, dynamic>?;

      if (token is! String || token.isEmpty) {
        emit(AuthError('Invalid OTP'));
        return;
      }

      await _storage.write(key: StorageKeys.authToken, value: token);
      
      // Auto-select first team if only one
      if (teams.length == 1) {
        await _storage.write(key: StorageKeys.tenantId, value: teams.first['id'].toString());
        emit(Authenticated(token, user: user, businessProfile: businessProfile, teams: teams, members: members, tenantId: teams.first['id'].toString()));
        return;
      }

      emit(TenantSelectionRequired(token, teams));
    } on DioException catch (e) {
      emit(AuthError(e.response?.data['message'] ?? 'Login failed'));
    } catch (_) {
      emit(AuthError('Something went wrong'));
    }
  }
}
