import 'package:dressur/components/constant.dart';
import 'package:flutter/foundation.dart';
import 'package:sqflite/sqflite.dart' as sql;

class SQLHelper {
  static Future<void> createTables(sql.Database database) async {
    await database.execute(createUserInfosTable);
    await database.execute(createDiscussionTable);
    await database.execute(createMessageTable);
  }

  static Future<sql.Database> dbOLD() async {
    return sql.openDatabase(
      oldDatabaseName,
      version: 1,
      onCreate: (sql.Database database, int version) async {
        await createTables(database);
      },
    );
  }

  static Future<sql.Database> db() async {
    return sql.openDatabase(
      nowDataBaseName,
      version: 1,
      onCreate: (sql.Database database, int version) async {
        await createTables(database);
      },
    );
  }

  static Future<int> insert(var tableName, var data) async {
    final db = await SQLHelper.db();
    final result = await db.insert(tableName, data,
        conflictAlgorithm: sql.ConflictAlgorithm.replace);
    return result;
  }

  static Future<List<Map<String, dynamic>>> getOneDiscussion(var uid) async {
    final db = await SQLHelper.db();
    return db.query(
      "discussion",
      where: "uid = ?",
      whereArgs: [uid],
      orderBy: "id",
      limit: 1,
    );
  }

  static Future<List<Map<String, dynamic>>> getAllMessages(
      String uid, String uid2) async {
    final db = await SQLHelper.db();
    return db.query(
      "message",
      where:
          "(emetteur = ? AND recepteur = ?) OR (emetteur = ? AND recepteur = ?)",
      whereArgs: [uid, uid2, uid2, uid],
      orderBy: "id DESC",
    );
  }

  static Future<List<Map<String, dynamic>>> getAllDiscussions() async {
    final db = await SQLHelper.db();
    return db.query("discussion", orderBy: "date DESC");
  }

  static Future<int> updateDiscussionDate(var uid, int newDate) async {
    final db = await SQLHelper.db();
    final data = {'date': newDate};
    return db.update(
      'discussion',
      data,
      where: 'uid = ?',
      whereArgs: [uid],
      conflictAlgorithm: sql.ConflictAlgorithm.replace,
    );
  }

  static Future<List<Map<String, dynamic>>> getLastMessageAndUnreadCount(
      String uid, String uid2) async {
    final db = await SQLHelper.db();

    // Récupérer le dernier message de la discussion
    final lastMessageQuery = await db.query(
      "message",
      where:
          "(emetteur = ? AND recepteur = ?) OR (emetteur = ? AND recepteur = ?)",
      whereArgs: [uid, uid2, uid2, uid],
      orderBy: "id DESC",
      limit: 1,
    );

    if (lastMessageQuery.isEmpty) {
      return []; // Aucun message trouvé
    }

    final lastMessage = lastMessageQuery.first;

    // Récupérer le nombre de messages non lus
    final unreadMessagesQuery = await db.query(
      "message",
      where:
          "((emetteur = ? AND recepteur = ?) OR (emetteur = ? AND recepteur = ?)) AND vue = ?",
      whereArgs: [uid, uid2, uid2, uid, "non"],
    );

    return [lastMessage, ...unreadMessagesQuery];
  }

  static Future<void> markAllMessagesAsRead(
      String uidUser, String uidOtherUser) async {
    final db = await SQLHelper.db();
    await db.update(
      'message',
      {'vue': 'oui'},
      where:
          "(emetteur = ? AND recepteur = ?) OR (emetteur = ? AND recepteur = ?)",
      whereArgs: [uidUser, uidOtherUser, uidOtherUser, uidUser],
    );
  }

  static Future<void> delete(String tableName) async {
    final db = await SQLHelper.db();
    try {
      await db
          .delete("userInfos", where: "tableName = ?", whereArgs: [tableName]);
    } catch (err) {
      debugPrint("Something went wrong when deleting an item: $err");
    }
  }

  static Future<void> deleteContactsAdd(String telAdd) async {
    final db = await SQLHelper.db();
    try {
      await db.delete("userInfos", where: "telAdd = ?", whereArgs: [telAdd]);
    } catch (err) {
      debugPrint("Something went wrong when deleting an item: $err");
    }
  }

  static Future<void> viderLaBaseDeDonneeLocal() async {
    final db = await SQLHelper.db();
    try {
      await db.delete("userInfos",
          where: "tableName != ?", whereArgs: ["numsTelUser"]);
      // await db.delete("userInfos", where: "idDS >= ?", whereArgs: [0]);
    } catch (err) {
      debugPrint("Something went wrong when deleting an item: $err");
    }
  }

  static Future<void> viderLaBaseDeDonneeLocalTelUser() async {
    final db = await SQLHelper.db();
    try {
      await db.delete("userInfos",
          where: "tableName == ?", whereArgs: ["numsTelUser"]);
      // await db.delete("userInfos", where: "idDS >= ?", whereArgs: [0]);
    } catch (err) {
      debugPrint("Something went wrong when deleting an item: $err");
    }
  }

  static Future<List<Map<String, dynamic>>> getAll(String tableName) async {
    final db = await SQLHelper.db();
    return db.query("userInfos",
        where: "tableName = ?", whereArgs: [tableName], orderBy: "id");
  }

  static Future<List<Map<String, dynamic>>> getOne(String tableName) async {
    final db = await SQLHelper.db();
    return db.query(
      "userInfos",
      where: "tableName = ?",
      whereArgs: [tableName],
      orderBy: "id",
      limit: 1,
    );
  }

  static Future<List<Map<String, dynamic>>> getOneNumsTelUser(
      String contactTel) async {
    final db = await SQLHelper.db();
    return db.query(
      "userInfos",
      where: "contactTel = ?",
      whereArgs: [contactTel],
      orderBy: "id",
      limit: 1,
    );
  }

  static Future<List<Map<String, dynamic>>> getUidUser() async {
    final db = await SQLHelper.db();
    return await db.query("userInfos",
        where: "tableName = ?", whereArgs: ["user"], orderBy: "id");
  }

  static Future<List<Map<String, dynamic>>> getUidUserOld() async {
    final db = await SQLHelper.dbOLD();
    return await db.query("userInfos",
        where: "tableName = ?", whereArgs: ["user"], orderBy: "id");
  }

  static Future<List<Map<String, dynamic>>> getFormulBoostWhithId(id) async {
    final db = await SQLHelper.db();
    return await db.query("userInfos",
        where: "value = ?", whereArgs: [id], orderBy: "id");
  }

  static Future<List<Map<String, dynamic>>> getYouHaveConnexion() async {
    final db = await SQLHelper.db();
    return await db.query("userInfos",
        where: "tableName = ?", whereArgs: ["youHaveConnexion"], orderBy: "id");
  }

  static Future<void> removeInLocalDataBase(idDS) async {
    final db = await SQLHelper.db();
    try {
      await db.delete("userInfos", where: "idDS = ?", whereArgs: [idDS]);
    } catch (err) {
      debugPrint("Something went wrong when deleting an item: $err");
    }
  }
}
