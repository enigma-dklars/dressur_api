package com.dressur.ds

import android.accounts.AccountManager
import io.flutter.embedding.android.FlutterActivity
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.plugin.common.MethodChannel

class MainActivity : FlutterActivity() {

    private val CHANNEL = "com.dressur.ds/accounts"

    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)
        MethodChannel(flutterEngine.dartExecutor.binaryMessenger, CHANNEL).setMethodCallHandler { call, result ->
            if (call.method == "getDeviceAccounts") {
                try {
                    val am = AccountManager.get(this)
                    val accounts = am.accounts
                    val list = accounts.map { acc ->
                        mapOf("name" to acc.name, "type" to acc.type)
                    }
                    result.success(list)
                } catch (e: Exception) {
                    result.error("UNAVAILABLE", "Cannot fetch accounts: ${e.message}", null)
                }
            } else {
                result.notImplemented()
            }
        }
    }
}
