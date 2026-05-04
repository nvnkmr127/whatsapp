import 'package:flutter_bloc/flutter_bloc.dart';
import '../../core/api_service.dart';

abstract class CannedEvent {}
class FetchCannedMessages extends CannedEvent {}

abstract class CannedState {}
class CannedInitial extends CannedState {}
class CannedLoaded extends CannedState {
  final List<dynamic> messages;
  CannedLoaded(this.messages);
}

class CannedBloc extends Bloc<CannedEvent, CannedState> {
  final ApiService _apiService;

  CannedBloc(this._apiService) : super(CannedInitial()) {
    on<FetchCannedMessages>((event, emit) async {
      final response = await _apiService.getCannedMessages();
      emit(CannedLoaded(response.data));
    });
  }
}
