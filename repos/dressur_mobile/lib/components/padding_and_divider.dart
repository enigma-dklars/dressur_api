import 'package:flutter/material.dart';

Widget DressurDivider() {
  return const Padding(
    padding: EdgeInsets.only(left: 50, top: 2, right: 50, bottom: 2),
    child: Divider(
      height: 1,
      thickness: 1,
      color: Colors.grey,
    ),
  );
}
