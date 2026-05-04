# Profile & Tests Plan

**Goal:** Ensure the Profile Screen reads dynamically from the user's fetched API credentials. Follow up with unit tests.

**Architecture:** 
1. Modify `Authenticated` state in `AuthBloc` to also store the `Map<String, dynamic> user`. 
2. Populate the `user` property inside `_onAppStarted` (using `/me`) and `_onLoginRequested` (from the response).
3. Update `ProfileScreen` to display the dynamic values instead of hardcoded strings.
4. Implement testing for `AuthBloc`.

**Tech Stack:** Flutter, Bloc, `flutter_test`.

---

### Task 1: Update AuthState and AuthBloc

- [ ] **Step 1: Modify `Authenticated` state**
  - In `lib/logic/blocs/auth_bloc.dart`, update `class Authenticated` to include `final Map<String, dynamic>? user;` and its constructor `Authenticated(this.token, {this.user});`.
  
- [ ] **Step 2: Update `_onAppStarted`**
  - Parse the user out of the `/me` call: `final me = await _apiService.getMe();` `final user = me.data['user'];`.
  - Pass the user to `emit(Authenticated(token, user: user));`.

- [ ] **Step 3: Update `_onLoginRequested`**
  - Pass `user: response.data['user']` to the `Authenticated` state emit.

- [ ] **Step 4: Update `_onTenantSelected`**
  - Add logic to fetch `/me` again or just leave the user null (or cache it in SecureStorage) so we can emit `Authenticated(token, user: ...)`.

### Task 2: Update ProfileScreen

- [ ] **Step 1: Wire the data in `ProfileScreen`**
  - In `lib/presentation/screens/profile_screen.dart`, access `state.user` when `state is Authenticated`.
  - Replace `'Active Agent'` with `state.user?['name'] ?? 'Active Agent'`.
  - Replace `'agent@yourteam.com'` with `state.user?['email'] ?? 'Unknown'`.

### Task 3: Write Unit Tests

- [ ] **Step 1: Setup bloc_test dependency**
  - Ensure `bloc_test` is in `pubspec.yaml` or add it to `dev_dependencies`.
  
- [ ] **Step 2: Write AuthBloc tests**
  - Create `test/logic/blocs/auth_bloc_test.dart`.
  - Mock `ApiService` and `FlutterSecureStorage`.
  - Test successful login and AppStarted events.