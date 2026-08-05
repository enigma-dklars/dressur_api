import 'package:art_sweetalert/art_sweetalert.dart';

void voirPlusAdd(titre, text, context) async{
  ArtSweetAlert.show(
    context: context,
    artDialogArgs: ArtDialogArgs(
      type: ArtSweetAlertType.info,
      title: titre,
      text: text
    )
  );
}

void dangerNoti(titre, text, context) async{
  ArtSweetAlert.show(
    context: context,
    artDialogArgs: ArtDialogArgs(
      type: ArtSweetAlertType.danger,
      title: titre,
      text: text
    )
  );
}

void infoNoti(titre, text, context) async{
  ArtSweetAlert.show(
    context: context,
    artDialogArgs: ArtDialogArgs(
      type: ArtSweetAlertType.info,
      title: titre,
      text: text
    )
  );
}

void questionNoti(titre, text, context) async{
  ArtSweetAlert.show(
    context: context,
    artDialogArgs: ArtDialogArgs(
      type: ArtSweetAlertType.question,
      title: titre,
      text: text
    )
  );
}

void successNoti(titre, text, context) async{
  ArtSweetAlert.show(
    context: context,
    artDialogArgs: ArtDialogArgs(
      type: ArtSweetAlertType.success,
      title: titre,
      text: text
    )
  );
}

void warningNoti(titre, text, context) async{
  ArtSweetAlert.show(
    context: context,
    artDialogArgs: ArtDialogArgs(
      type: ArtSweetAlertType.warning,
      title: titre,
      text: text
    )
  );
}